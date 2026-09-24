<?php

namespace Tests\Unit;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Http\Discovery\ClassDiscovery;
use OnPay\API\Exception\ApiException;
use OnPay\API\Exception\ConnectionException;
use OnPay\Http\LoggingHttpClient;
use OnPay\Http\Psr18HttpClient;
use OnPay\OnPayAPI;
use OnPay\TokenStorageInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

class PluggableHttpClientTest extends TestCase {
    protected string $clientId = 'test_client_id';
    protected string $baseUri = 'test_base_uri';
    protected string $baseAuthUri = 'test_base_authorize_uri';
    protected string $redirectUri = 'test_redirect_uri';

    /**
     * @return array<string,string>
     */
    private function options(): array {
        return [
            'base_uri' => $this->baseUri,
            'base_authorize_uri' => $this->baseAuthUri,
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
        ];
    }

    private function validTokenStorage(): TokenStorageInterface {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($this->getToken(time(), 3600));
        return $tokenStorage;
    }

    /**
     * Unwraps down to the PSR-18 client the SDK actually sends through.
     */
    private function getPsr18Client(OnPayAPI $api): ClientInterface {
        $transport = $this->getHttpClient($api);
        $this->assertInstanceOf(Psr18HttpClient::class, $transport);

        $client = (new \ReflectionProperty(Psr18HttpClient::class, 'client'))->getValue($transport);
        $this->assertInstanceOf(ClientInterface::class, $client);

        return $client;
    }

    /**
     * Runs $test with $strategy consulted before php-http/discovery's own strategies.
     */
    private function withDiscoveryStrategy(string $strategy, callable $test): void {
        $originalStrategies = (new \ReflectionProperty(ClassDiscovery::class, 'strategies'))->getValue();

        try {
            ClassDiscovery::prependStrategy($strategy);
            ClassDiscovery::clearCache();

            $test();
        } finally {
            ClassDiscovery::setStrategies($originalStrategies);
            ClassDiscovery::clearCache();
        }
    }

    private function getHttpClient(OnPayAPI $api): object {
        // Private members are reflection-accessible without setAccessible() on PHP 8.1+.
        $apiClient = (new \ReflectionProperty(OnPayAPI::class, 'apiClient'))->getValue($api);
        $httpClient = (new \ReflectionProperty(\OnPay\Http\ApiClient::class, 'httpClient'))->getValue($apiClient);
        $this->assertInstanceOf(LoggingHttpClient::class, $httpClient);

        // The transport is wrapped in the PSR-3 decorator; unwrap it to assert on the transport tier.
        return (new \ReflectionProperty(LoggingHttpClient::class, 'inner'))->getValue($httpClient);
    }

    public function testInjectedPsr18ClientBuildsAndSendsRequest(): void {
        $capturedRequest = null;
        $psrClient = $this->createMock(ClientInterface::class);
        $psrClient->method('sendRequest')->willReturnCallback(function (RequestInterface $request) use (&$capturedRequest) {
            $capturedRequest = $request;
            return new GuzzleResponse(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => ['pong' => 'merchant_id'],
            ]));
        });

        $factory = new HttpFactory();
        $api = new OnPayAPI($this->validTokenStorage(), $this->options(), null, $psrClient, $factory, $factory);

        $result = $api->ping();

        // The injected client actually handled the traffic.
        $this->assertInstanceOf(Psr18HttpClient::class, $this->getHttpClient($api));

        // The PSR-7 request the SDK built is the expected API GET.
        $this->assertNotNull($capturedRequest);
        $this->assertSame('GET', $capturedRequest->getMethod());
        $this->assertSame($this->baseUri . '/v1/ping', (string) $capturedRequest->getUri());
        $this->assertSame('Bearer test_access_token', $capturedRequest->getHeaderLine('Authorization'));
        $this->assertSame($api->getPlatform(), $capturedRequest->getHeaderLine('User-Agent'));

        // The mocked PSR-7 response body is parsed and returned.
        $this->assertSame(['data' => ['pong' => 'merchant_id']], $result);
    }

    public function testDebugApiIsPopulatedOnInjectedPath(): void {
        $psrClient = $this->createMock(ClientInterface::class);
        $psrClient->method('sendRequest')->willReturn(
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], json_encode(['data' => []]))
        );

        $factory = new HttpFactory();
        $api = new OnPayAPI($this->validTokenStorage(), $this->options(), null, $psrClient, $factory, $factory);

        $api->ping();

        // The debug API must be populated from the recorded PSR-7 messages.
        $lastRequest = $api->getLastHttpRequest();
        $this->assertSame('GET', $lastRequest->getMethod());
        $this->assertSame($this->baseUri . '/v1/ping', $lastRequest->getUri());
        $this->assertSame('Bearer test_access_token', $lastRequest->getHeaders()['Authorization']);

        $lastResponse = $api->getLastHttpResponse();
        $this->assertSame(200, $lastResponse->getStatusCode());
        $this->assertSame(json_encode(['data' => []]), $lastResponse->getBody());
    }

    public function testTransportFailureIsMappedToConnectionException(): void {
        $psrException = new class('network down') extends \RuntimeException implements ClientExceptionInterface {};

        $psrClient = $this->createMock(ClientInterface::class);
        $psrClient->method('sendRequest')->willThrowException($psrException);

        $factory = new HttpFactory();
        $api = new OnPayAPI($this->validTokenStorage(), $this->options(), null, $psrClient, $factory, $factory);

        $this->expectException(ConnectionException::class);
        $api->ping();
    }

    public function testErrorResponseHeadersAreFlattenedForErrorParsing(): void {
        // A non-2xx JSON response must drive the error-body parsing branch, which
        // requires the PSR-7 Content-Type header to be read correctly.
        $psrClient = $this->createMock(ClientInterface::class);
        $psrClient->method('sendRequest')->willReturn(
            new GuzzleResponse(400, ['Content-Type' => 'application/json'], json_encode([
                'errors' => [['message' => 'boom']],
            ]))
        );

        $factory = new HttpFactory();
        $api = new OnPayAPI($this->validTokenStorage(), $this->options(), null, $psrClient, $factory, $factory);

        try {
            $api->ping();
            $this->fail('Expected ApiException was not thrown');
        } catch (ApiException $e) {
            $this->assertSame('boom', $e->getMessage());
            $this->assertSame(400, $e->getCode());
        }
    }

    public function testDiscoveryTierResolvesInstalledPsrStack(): void {
        // With a PSR-18 client (Guzzle, via require-dev / league) installed, the
        // no-config constructor must discover and use it.
        $api = new OnPayAPI($this->validTokenStorage(), $this->options());

        $this->assertInstanceOf(Psr18HttpClient::class, $this->getHttpClient($api));
    }

    public function testDiscoveredGuzzleClientGetsATimeout(): void {
        // Guzzle's default is no timeout at all; a client the SDK discovers itself
        // is rebuilt with one, since nobody else had the chance to configure it.
        $api = new OnPayAPI($this->validTokenStorage(), $this->options());

        $transport = $this->getPsr18Client($api);
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $transport);
        $this->assertSame(30, $transport->getConfig('timeout'));
        $this->assertSame(5, $transport->getConfig('connect_timeout'));
    }

    public function testDiscoveredNonGuzzleClientIsUsedAsFound(): void {
        // Any other discovered client is the consumer's choice and is used untouched.
        $this->withDiscoveryStrategy(NonGuzzleClientStrategy::class, function (): void {
            $api = new OnPayAPI($this->validTokenStorage(), $this->options());

            $this->assertInstanceOf(NonGuzzlePsr18Client::class, $this->getPsr18Client($api));
        });
    }

    public function testInjectedGuzzleClientKeepsItsOwnConfiguration(): void {
        $injected = new \GuzzleHttp\Client(['timeout' => 3]);

        $api = new OnPayAPI($this->validTokenStorage(), $this->options(), null, $injected);

        $this->assertSame($injected, $this->getPsr18Client($api));
    }

    public function testInjectedClientDiscoversMissingFactories(): void {
        $psrClient = $this->createMock(ClientInterface::class);
        $psrClient->method('sendRequest')->willReturn(
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], json_encode(['data' => ['pong' => 'ok']]))
        );

        // Only the client is injected; the PSR-17 factories are auto-discovered.
        $api = new OnPayAPI($this->validTokenStorage(), $this->options(), null, $psrClient);

        $this->assertInstanceOf(Psr18HttpClient::class, $this->getHttpClient($api));
        $this->assertSame(['data' => ['pong' => 'ok']], $api->ping());
    }

    public function testThrowsWhenNoPsrStackAvailable(): void {
        // The SDK ships no HTTP client: when discovery finds nothing and nothing is
        // injected, construction must fail loudly instead of silently picking a transport.
        $this->withoutDiscovery(function (): void {
            try {
                new OnPayAPI($this->validTokenStorage(), $this->options());
                $this->fail('Expected InvalidArgumentException was not thrown');
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('No PSR-18 HTTP client', $e->getMessage());
                $this->assertInstanceOf(\Http\Discovery\Exception\NotFoundException::class, $e->getPrevious());
            }
        });
    }

    public function testThrowsWhenClientInjectedButNoFactoriesAvailable(): void {
        $psrClient = $this->createMock(ClientInterface::class);

        $this->withoutDiscovery(function () use ($psrClient): void {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('PSR-17 request/stream factory');

            new OnPayAPI($this->validTokenStorage(), $this->options(), null, $psrClient);
        });
    }

    public function testInjectedStackNeedsNoDiscovery(): void {
        $factory = new HttpFactory();
        $psrClient = $this->createMock(ClientInterface::class);
        $psrClient->method('sendRequest')->willReturn(
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], json_encode(['data' => ['pong' => 'ok']]))
        );

        $this->withoutDiscovery(function () use ($psrClient, $factory): void {
            $api = new OnPayAPI($this->validTokenStorage(), $this->options(), null, $psrClient, $factory, $factory);

            $this->assertSame(['data' => ['pong' => 'ok']], $api->ping());
            $this->assertStringStartsWith($this->baseAuthUri . '/oauth2/authorize?', $api->authorize());
        });
    }

    /**
     * Runs $test with php-http/discovery configured to find nothing.
     */
    private function withoutDiscovery(callable $test): void {
        $originalStrategies = (new \ReflectionProperty(ClassDiscovery::class, 'strategies'))->getValue();

        try {
            ClassDiscovery::setStrategies([]);
            ClassDiscovery::clearCache();

            $test();
        } finally {
            ClassDiscovery::setStrategies($originalStrategies);
            ClassDiscovery::clearCache();
        }
    }

    protected function getToken(int $issuedAt, int $expiresIn, string $accessToken = 'test_access_token', string $refreshToken = 'test_refresh_token'): string {
        $token = [
            'provider_id' => $this->baseAuthUri . '/oauth2/authorize|' . $this->clientId,
            'issued_at' => date('Y-m-d H:i:s', $issuedAt),
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
            'scope' => 'full',
        ];
        if ('' !== $accessToken) {
            $token['access_token'] = $accessToken;
        }
        if ('' !== $refreshToken) {
            $token['refresh_token'] = $refreshToken;
        }
        return json_encode($token);
    }
}

/**
 * A PSR-18 client that is not Guzzle, for the discovery tests.
 */
final class NonGuzzlePsr18Client implements ClientInterface {
    public function sendRequest(RequestInterface $request): \Psr\Http\Message\ResponseInterface {
        return new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"data":{"pong":"ok"}}');
    }
}

/**
 * Makes php-http/discovery hand out {@see NonGuzzlePsr18Client} as the PSR-18 client.
 */
final class NonGuzzleClientStrategy implements \Http\Discovery\Strategy\DiscoveryStrategy {
    public static function getCandidates($type) {
        return ClientInterface::class === $type
            ? [['class' => NonGuzzlePsr18Client::class, 'condition' => true]]
            : [];
    }
}
