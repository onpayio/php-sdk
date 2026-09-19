<?php

namespace Tests\Unit;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Http\Discovery\ClassDiscovery;
use OnPay\API\Exception\ApiException;
use OnPay\API\Exception\ConnectionException;
use OnPay\CurlHttpClientLogger;
use OnPay\Http\LoggingHttpClient;
use OnPay\Http\Psr18HttpClient;
use OnPay\OAuth\Client\Http\Request as OAuthRequest;
use OnPay\OAuth\Client\Http\Response as OAuthResponse;
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

    private function getHttpClient(OnPayAPI $api): object {
        // Private members are reflection-accessible without setAccessible() on PHP 8.1+.
        $httpClient = (new \ReflectionProperty(OnPayAPI::class, 'httpClient'))->getValue($api);
        $this->assertInstanceOf(LoggingHttpClient::class, $httpClient);

        // The transport is wrapped in the PSR-3 decorator; unwrap it to assert on the transport tier.
        return $httpClient->getInnerClient();
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
        $api = new OnPayAPI($this->validTokenStorage(), $this->options(), $psrClient, $factory, $factory);

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
        $api = new OnPayAPI($this->validTokenStorage(), $this->options(), $psrClient, $factory, $factory);

        $api->ping();

        // Guards the instanceof-converter trap: the debug API must be populated
        // even when a PSR-18 client (not the cURL logger) is used.
        $lastRequest = $api->getLastHttpRequest();
        $this->assertSame('GET', $lastRequest->getMethod());
        $this->assertSame($this->baseUri . '/v1/ping', $lastRequest->getUri());
        $this->assertSame('Bearer test_access_token', $lastRequest->getHeaders()['Authorization']);

        $lastResponse = $api->getLastHttpResponse();
        $this->assertSame(200, $lastResponse->getStatusCode());
        $this->assertSame(json_encode(['data' => []]), $lastResponse->getBody());
    }

    public function testDebugApiIsPopulatedOnBundledCurlPath(): void {
        // The bundled cURL logger path: OnPayAPI must convert the recorder's OAuth
        // DTOs into the public API\Http DTOs. Real network I/O through the cURL client
        // is exercised by the 6320 test harness, not here — this covers the
        // OnPayAPI-side capture/conversion using the CurlHttpClientLogger type.
        // Run the real constructor (curlInit) so the mock's __destruct (curl_close)
        // is safe; we override send()/getLast* so no network I/O happens.
        $httpClient = $this->getMockBuilder(CurlHttpClientLogger::class)
            ->setConstructorArgs([[]])
            ->getMock();
        $httpClient->method('send')->willReturn(
            new OAuthResponse(200, json_encode(['data' => []]), ['Content-Type' => 'application/json'])
        );
        $httpClient->method('getLastRequest')->willReturn(
            new OAuthRequest('GET', $this->baseUri . '/v1/ping', ['User-Agent' => 'sdk-test'])
        );
        $httpClient->method('getLastResponse')->willReturn(
            new OAuthResponse(200, 'recorded-body', ['Content-Type' => 'application/json'])
        );

        $api = new OnPayAPI($this->validTokenStorage(), $this->options());
        (new \ReflectionProperty(OnPayAPI::class, 'httpClient'))->setValue($api, $httpClient);

        $api->ping();

        $lastRequest = $api->getLastHttpRequest();
        $this->assertSame('GET', $lastRequest->getMethod());
        $this->assertSame($this->baseUri . '/v1/ping', $lastRequest->getUri());
        $this->assertSame(['User-Agent' => 'sdk-test'], $lastRequest->getHeaders());

        $lastResponse = $api->getLastHttpResponse();
        $this->assertSame(200, $lastResponse->getStatusCode());
        $this->assertSame('recorded-body', $lastResponse->getBody());
    }

    public function testTransportFailureIsMappedToConnectionException(): void {
        $psrException = new class('network down') extends \RuntimeException implements ClientExceptionInterface {};

        $psrClient = $this->createMock(ClientInterface::class);
        $psrClient->method('sendRequest')->willThrowException($psrException);

        $factory = new HttpFactory();
        $api = new OnPayAPI($this->validTokenStorage(), $this->options(), $psrClient, $factory, $factory);

        $this->expectException(ConnectionException::class);
        $api->ping();
    }

    public function testErrorResponseHeadersAreFlattenedForErrorParsing(): void {
        // A non-2xx JSON response must drive the same error-body parsing branch as
        // the cURL path, which requires PSR-7 headers to be flattened correctly.
        $psrClient = $this->createMock(ClientInterface::class);
        $psrClient->method('sendRequest')->willReturn(
            new GuzzleResponse(400, ['Content-Type' => 'application/json'], json_encode([
                'errors' => [['message' => 'boom']],
            ]))
        );

        $factory = new HttpFactory();
        $api = new OnPayAPI($this->validTokenStorage(), $this->options(), $psrClient, $factory, $factory);

        try {
            $api->ping();
            $this->fail('Expected ApiException was not thrown');
        } catch (ApiException $e) {
            $this->assertSame('boom', $e->getMessage());
            $this->assertSame(400, $e->getCode());
        }
    }

    public function testDiscoveryTierResolvesInstalledPsrStack(): void {
        // With guzzlehttp/guzzle installed (require-dev), the no-config constructor
        // must discover and use the PSR-18 stack, not the cURL default.
        $api = new OnPayAPI($this->validTokenStorage(), $this->options());

        $this->assertInstanceOf(Psr18HttpClient::class, $this->getHttpClient($api));
    }

    public function testInjectedClientDiscoversMissingFactories(): void {
        $psrClient = $this->createMock(ClientInterface::class);
        $psrClient->method('sendRequest')->willReturn(
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], json_encode(['data' => ['pong' => 'ok']]))
        );

        // Only the client is injected; the PSR-17 factories are auto-discovered.
        $api = new OnPayAPI($this->validTokenStorage(), $this->options(), $psrClient);

        $this->assertInstanceOf(Psr18HttpClient::class, $this->getHttpClient($api));
        $this->assertSame(['data' => ['pong' => 'ok']], $api->ping());
    }

    public function testFallsBackToBundledCurlClientWhenNoPsrStackAvailable(): void {
        // Force discovery to find nothing, then confirm the cURL client is used.
        $originalStrategies = (new \ReflectionProperty(ClassDiscovery::class, 'strategies'))->getValue();

        try {
            ClassDiscovery::setStrategies([]);
            ClassDiscovery::clearCache();

            $api = new OnPayAPI($this->validTokenStorage(), $this->options());

            $this->assertInstanceOf(CurlHttpClientLogger::class, $this->getHttpClient($api));
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
