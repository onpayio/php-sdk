<?php

namespace Tests\Unit\Core;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use OnPay\API\Exception\ApiException;
use OnPay\API\Exception\ConnectionException;
use OnPay\API\Exception\TokenException;
use OnPay\API\GatewayService;
use OnPay\API\Http\Request as HttpRequest;
use OnPay\API\Http\Response as HttpResponse;
use OnPay\API\PaymentService;
use OnPay\API\SubscriptionService;
use OnPay\API\TransactionService;
use OnPay\OnPayAPI;
use OnPay\StaticToken;
use OnPay\TokenStorageInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Tests\Support\ApiTestCase;

/**
 * Drives the real OnPayAPI end-to-end against the {@see \Tests\Support\FakeHttpClient}
 * harness with inline-arranged responses (no fixtures, no network) to exercise the
 * request-construction, handleResponse and OAuth-wiring branches of OnPayAPI directly.
 *
 * The HTTP-client *resolution* tiers (discovery / cURL fallback / injected-factory
 * discovery) are covered by {@see \Tests\Unit\PluggableHttpClientTest}; the one genuine
 * gap there (a provided client with no discoverable PSR-17 factory) lives in
 * {@see HttpClientResolutionTest}.
 */
class OnPayApiCoreTest extends ApiTestCase
{
    // ---------------------------------------------------------------------
    // get() + ping() happy path and request construction
    // ---------------------------------------------------------------------

    public function testPingBuildsAuthenticatedGetAndReturnsParsedBody(): void
    {
        $this->http->willReturnJson(['data' => ['pong' => 'merchant_id']], 200, 'GET', 'ping');

        $api = $this->createApi();

        $result = $api->ping();

        // Parsed JSON body is returned.
        $this->assertSame(['data' => ['pong' => 'merchant_id']], $result);

        // The debug DTO captures the exact wire shape of the GET the SDK built.
        $lastRequest = $api->getLastHttpRequest();
        $this->assertInstanceOf(HttpRequest::class, $lastRequest);
        $this->assertSame('GET', $lastRequest->getMethod());
        $this->assertSame(self::BASE_URI . '/v1/ping', $lastRequest->getUri());
        $this->assertSame($api->getPlatform(), $lastRequest->getHeaders()['User-Agent']);
        $this->assertSame($this->expectedAuthorizationHeader(), $lastRequest->getHeaders()['Authorization']);

        // The wire request the fake actually received carries the Bearer + User-Agent.
        $wire = $this->http->getLastRequest();
        $this->assertSame('GET', $wire->getMethod());
        $this->assertSame(self::BASE_URI . '/v1/ping', (string) $wire->getUri());
        $this->assertSame($this->expectedAuthorizationHeader(), $wire->getHeaderLine('Authorization'));
    }

    public function testGetCapturesResponseStatusAndBodyInDebugDto(): void
    {
        $body = ['data' => ['id' => '123']];
        $this->http->willReturnJson($body, 200, 'GET', 'transaction/123');

        $api = $this->createApi();
        $api->get('transaction/123');

        $lastResponse = $api->getLastHttpResponse();
        $this->assertInstanceOf(HttpResponse::class, $lastResponse);
        $this->assertSame(200, $lastResponse->getStatusCode());
        $this->assertSame(json_encode($body, JSON_UNESCAPED_SLASHES), $lastResponse->getBody());
    }

    // ---------------------------------------------------------------------
    // post() request construction + body encoding
    // ---------------------------------------------------------------------

    public function testPostBuildsJsonRequestWithUnescapedSlashesAndReturnsParsedBody(): void
    {
        $this->http->willReturnJson(['data' => ['created' => true]], 200, 'POST', 'subscription');

        $api = $this->createApi();

        $payload = ['website' => 'https://example.test/return'];
        $result = $api->post('subscription', $payload);

        $this->assertSame(['data' => ['created' => true]], $result);

        $lastRequest = $api->getLastHttpRequest();
        $this->assertSame('POST', $lastRequest->getMethod());
        $this->assertSame(self::BASE_URI . '/v1/subscription', $lastRequest->getUri());
        $this->assertSame('application/json', $lastRequest->getHeaders()['Content-Type']);
        $this->assertSame($api->getPlatform(), $lastRequest->getHeaders()['User-Agent']);

        // Pins JSON_UNESCAPED_SLASHES: the URL's slashes are NOT escaped. This also
        // exercises API\Http\Request::getBody().
        $this->assertSame('{"website":"https://example.test/return"}', $lastRequest->getBody());
    }

    // ---------------------------------------------------------------------
    // handleResponse() error branches
    // ---------------------------------------------------------------------

    public function testNotFoundOverridesMessage(): void
    {
        $this->http->willReturnJson(['errors' => [['message' => 'ignored']]], 404, 'GET', 'transaction/nope');

        $api = $this->createApi();

        try {
            $api->get('transaction/nope');
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame('Not found', $e->getMessage());
            $this->assertSame(404, $e->getCode());
        }
    }

    public function testErrorBodyMessageIsParsedFromErrorsArray(): void
    {
        $this->http->willReturnJson(['errors' => [['message' => 'boom']]], 400, 'GET', 'transaction/x');

        $api = $this->createApi();

        try {
            $api->get('transaction/x');
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame('boom', $e->getMessage());
            $this->assertSame(400, $e->getCode());
        }
    }

    public function testForbiddenBecomesTokenExceptionWithParsedMessage(): void
    {
        $this->http->willReturn(
            new Response(403, ['Content-Type' => 'application/json'], json_encode(['errors' => [['message' => 'Access denied']]])),
            'GET',
            'transaction/x'
        );

        $api = $this->createApi();

        try {
            $api->get('transaction/x');
            $this->fail('Expected TokenException');
        } catch (TokenException $e) {
            $this->assertSame('Access denied', $e->getMessage());
            $this->assertSame(403, $e->getCode());
        }
    }

    public function testErrorResponseWithoutJsonBodyYieldsEmptyMessage(): void
    {
        // Empty body + no JSON content-type: the error-body parse block is skipped
        // (first clause of the guard fails), so the message stays ''.
        $this->http->willReturn(new Response(500, [], ''), 'GET', 'transaction/x');

        $api = $this->createApi();

        try {
            $api->get('transaction/x');
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame('', $e->getMessage());
            $this->assertSame(500, $e->getCode());
        }
    }

    public function testUndecodableJsonErrorBodyThrowsApiException(): void
    {
        // Exact 'application/json' content-type but a malformed body drives the
        // json_last_error() branch.
        $this->http->willReturn(
            new Response(400, ['Content-Type' => 'application/json'], '{not valid json'),
            'GET',
            'transaction/x'
        );

        $api = $this->createApi();

        try {
            $api->get('transaction/x');
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertStringContainsString('Failed to decode JSON body-response', $e->getMessage());
            $this->assertStringContainsString('Syntax error', $e->getMessage());
            $this->assertSame(400, $e->getCode());
        }
    }

    public function testFalseResponseBecomesInvalidTokenException(): void
    {
        // A null stored token => OAuthClient::send() returns false => handleResponse(false).
        // No HTTP request is made, so nothing is arranged.
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $api = $this->createApi([], $tokenStorage);

        try {
            $api->get('ping');
            $this->fail('Expected TokenException');
        } catch (TokenException $e) {
            $this->assertSame('Invalid response. Possible invalid token.', $e->getMessage());
        }
    }

    // ---------------------------------------------------------------------
    // get()/post() exception translation
    // ---------------------------------------------------------------------

    public function testGetTransportFailureBecomesConnectionException(): void
    {
        $api = $this->createApiWithClient($this->throwingClient());

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('network down');
        $api->get('ping');
    }

    public function testPostTransportFailureBecomesConnectionException(): void
    {
        $api = $this->createApiWithClient($this->throwingClient());

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('network down');
        $api->post('subscription', ['a' => 'b']);
    }

    public function testGetMapsOAuthTokenExceptionToApiTokenException(): void
    {
        // Expired token + a failed (non invalid_grant) refresh => the OAuth client throws
        // its own TokenException, which get() re-wraps as API TokenException.
        $this->http->willReturnJson(['error' => 'server_error'], 500, 'POST', 'oauth2/access_token');

        $api = $this->createApi([], $this->expiredTokenStorage());

        try {
            $api->get('ping');
            $this->fail('Expected TokenException');
        } catch (TokenException $e) {
            $this->assertSame('unable to refresh access_token', $e->getMessage());
            // Pins the re-wrap: the API TokenException wraps the OAuth-layer TokenException.
            $this->assertInstanceOf(\OnPay\OAuth\Client\Exception\TokenException::class, $e->getPrevious());
        }
    }

    public function testPostMapsOAuthTokenExceptionToApiTokenException(): void
    {
        $this->http->willReturnJson(['error' => 'server_error'], 500, 'POST', 'oauth2/access_token');

        $api = $this->createApi([], $this->expiredTokenStorage());

        try {
            $api->post('subscription', ['a' => 'b']);
            $this->fail('Expected TokenException');
        } catch (TokenException $e) {
            $this->assertSame('unable to refresh access_token', $e->getMessage());
            // Pins the re-wrap: the API TokenException wraps the OAuth-layer TokenException.
            $this->assertInstanceOf(\OnPay\OAuth\Client\Exception\TokenException::class, $e->getPrevious());
        }
    }

    public function testGetMapsAccessTokenExceptionToApiTokenException(): void
    {
        // A stored token that parses (has 'provider_id') but is missing a required key
        // makes AccessToken construction throw AccessTokenException. get() catches the
        // (typo-cased) \OnPay\OAUth\...\AccessTokenException and re-wraps as API TokenException.
        // Current-behaviour note: this proves that catch is NOT dead code.
        $badToken = json_encode([
            'provider_id' => self::BASE_AUTHORIZE_URI . '/oauth2/authorize|' . self::CLIENT_ID,
            'issued_at' => date('Y-m-d H:i:s'),
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'full',
            // no access_token
        ]);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($badToken);

        $api = $this->createApi([], $tokenStorage);

        try {
            $api->get('ping');
            $this->fail('Expected TokenException');
        } catch (TokenException $e) {
            $this->assertStringContainsString('missing key "access_token"', $e->getMessage());
        }
    }

    // ---------------------------------------------------------------------
    // isAuthorized()
    // ---------------------------------------------------------------------

    public function testIsAuthorizedTrueWhenPingSucceeds(): void
    {
        $this->http->willReturnJson(['data' => ['pong' => 'ok']], 200, 'GET', 'ping');

        $api = $this->createApi();

        $this->assertTrue($api->isAuthorized());
    }

    public function testIsAuthorizedFalseWhenTokenInvalid(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $api = $this->createApi([], $tokenStorage);

        $this->assertFalse($api->isAuthorized());
    }

    // ---------------------------------------------------------------------
    // authorize() / finishAuthorize()
    // ---------------------------------------------------------------------

    public function testAuthorizeReturnsProviderUrlWithExpectedQuery(): void
    {
        $api = $this->createApi();

        $url = $api->authorize();

        $this->assertStringStartsWith(self::BASE_AUTHORIZE_URI . '/oauth2/authorize?', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame(self::CLIENT_ID, $query['client_id']);
        $this->assertSame(self::REDIRECT_URI, $query['redirect_uri']);
        $this->assertSame('full', $query['scope']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('S256', $query['code_challenge_method']);
    }

    public function testFinishAuthorizeExchangesCodeButDoesNotCaptureLastRequest(): void
    {
        // The state OnPayAPI sends is crypt(providerId, 'state'), matching what the
        // sessionless OnPay\Session stores, so the callback proceeds to the token POST.
        $this->http->willReturnJson([
            'access_token' => 'freshly_issued_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'fresh_refresh_token',
            'scope' => 'full',
        ], 200, 'POST', 'oauth2/access_token');

        $api = $this->createApi();

        $api->finishAuthorize('the-auth-code');

        // Current-behaviour finding: finishAuthorize() never calls
        // setLastHttpRequest/Response, so the debug DTOs stay unset even though a
        // token-exchange request was made.
        $this->assertNull($api->getLastHttpRequest());
        $this->assertNull($api->getLastHttpResponse());
    }

    // ---------------------------------------------------------------------
    // Constructor option branches
    // ---------------------------------------------------------------------

    public function testPlatformOptionOverridesDefault(): void
    {
        // Constructed WITHOUT a client => routes through the discovery tier while
        // asserting the platform option, not the resolved client.
        $api = new OnPayAPI($this->validTokenStorage(), [
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT_URI,
            'base_uri' => self::BASE_URI,
            'base_authorize_uri' => self::BASE_AUTHORIZE_URI,
            'platform' => 'woocommerce/7.3',
        ]);

        $this->assertSame('woocommerce/7.3', $api->getPlatform());
    }

    public function testDefaultPlatformIsSdkVersion(): void
    {
        $api = $this->createApi();

        $this->assertSame('php-sdk/' . OnPayAPI::SDK_VERSION, $api->getPlatform());
    }

    public function testRedirectUriDefaultsToEmptyForStaticToken(): void
    {
        // StaticToken makes redirect_uri optional; the constructor defaults it to ''.
        // Built without a client => also covers the discovery-tier resolution path.
        $api = new OnPayAPI(new StaticToken('static-api-token'), [
            'client_id' => self::CLIENT_ID,
            'base_uri' => self::BASE_URI,
            'base_authorize_uri' => self::BASE_AUTHORIZE_URI,
        ]);

        $url = $api->authorize();
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertSame('', $query['redirect_uri']);
    }

    // ---------------------------------------------------------------------
    // Service accessors (lazy singletons)
    // ---------------------------------------------------------------------

    public function testServiceAccessorsReturnCachedSingletons(): void
    {
        $api = $this->createApi();

        $transaction = $api->transaction();
        $subscription = $api->subscription();
        $payment = $api->payment();
        $gateway = $api->gateway();

        $this->assertInstanceOf(TransactionService::class, $transaction);
        $this->assertInstanceOf(SubscriptionService::class, $subscription);
        $this->assertInstanceOf(PaymentService::class, $payment);
        $this->assertInstanceOf(GatewayService::class, $gateway);

        // Second call returns the same cached instance.
        $this->assertSame($transaction, $api->transaction());
        $this->assertSame($subscription, $api->subscription());
        $this->assertSame($payment, $api->payment());
        $this->assertSame($gateway, $api->gateway());
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function createApiWithClient(ClientInterface $client, ?TokenStorageInterface $tokenStorage = null): OnPayAPI
    {
        $factory = new HttpFactory();

        return new OnPayAPI(
            $tokenStorage ?? $this->validTokenStorage(),
            [
                'client_id' => self::CLIENT_ID,
                'redirect_uri' => self::REDIRECT_URI,
                'base_uri' => self::BASE_URI,
                'base_authorize_uri' => self::BASE_AUTHORIZE_URI,
            ],
            $client,
            $factory,
            $factory
        );
    }

    private function throwingClient(): ClientInterface
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willThrowException(
            new class ('network down') extends \RuntimeException implements ClientExceptionInterface {}
        );

        return $client;
    }

    private function expiredTokenStorage(): TokenStorageInterface
    {
        $token = json_encode([
            'provider_id' => self::BASE_AUTHORIZE_URI . '/oauth2/authorize|' . self::CLIENT_ID,
            'issued_at' => date('Y-m-d H:i:s', time() - 7200),
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'full',
            'access_token' => self::ACCESS_TOKEN,
            'refresh_token' => 'test_refresh_token',
        ]);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        return $tokenStorage;
    }
}
