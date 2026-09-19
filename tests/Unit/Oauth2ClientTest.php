<?php

namespace Tests\Unit;

use OnPay\API\Exception\ConnectionException;
use OnPay\API\Exception\TokenException;
use OnPay\OnPayAPI;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Tests\Support\ApiTestCase;
use Tests\Unit\TokenStorage\FakeTokenStorage;

/**
 * The OAuth flow of OnPayAPI on top of league/oauth2-client: authorize URL, code
 * exchange, bearer requests, refresh and the failure modes, driven end-to-end through
 * the {@see \Tests\Support\FakeHttpClient}.
 */
class Oauth2ClientTest extends ApiTestCase {
    private FakeTokenStorage $tokenStorage;

    protected function setUp(): void {
        parent::setUp();
        $this->tokenStorage = new FakeTokenStorage();
    }

    public function testAuthorizeUrlIsExpectedFormat(): void {
        $url = $this->api()->authorize();

        $expectedPath = self::BASE_AUTHORIZE_URI . '/oauth2/authorize';
        $this->assertStringStartsWith($expectedPath . '?', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame(self::CLIENT_ID, $query['client_id']);
        $this->assertSame(self::REDIRECT_URI, $query['redirect_uri']);
        $this->assertSame('full', $query['scope']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('S256', $query['code_challenge_method']);
        // State and code challenge are random per call.
        $this->assertNotSame('', $query['state']);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $query['code_challenge']);
        // league's non-standard approval_prompt is not sent to OnPay.
        $this->assertArrayNotHasKey('approval_prompt', $query);
    }

    public function testAuthorizeSendsPkceChallengeMatchingTheVerifierUsedOnExchange(): void {
        $this->http->willReturnJson($this->tokenResponse('initial_access_token', 'initial_refresh_token'), 200, 'POST', 'oauth2/access_token');

        $api = $this->api();
        parse_str(parse_url($api->authorize(), PHP_URL_QUERY), $query);
        $api->finishAuthorize('test_finish_code');

        parse_str((string) $this->http->getLastRequest()->getBody(), $body);
        $expectedChallenge = rtrim(strtr(base64_encode(hash('sha256', $body['code_verifier'], true)), '+/', '-_'), '=');
        $this->assertSame($expectedChallenge, $query['code_challenge']);
    }

    public function testFinishAuthorize(): void {
        $this->http->willReturnJson($this->tokenResponse('initial_access_token', 'initial_refresh_token'), 200, 'POST', 'oauth2/access_token');

        $this->api()->finishAuthorize('test_finish_code');

        $request = $this->http->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(self::BASE_URI . '/oauth2/access_token', (string) $request->getUri());

        parse_str((string) $request->getBody(), $body);
        $this->assertSame(self::CLIENT_ID, $body['client_id']);
        $this->assertSame('authorization_code', $body['grant_type']);
        $this->assertSame('test_finish_code', $body['code']);
        $this->assertSame(self::REDIRECT_URI, $body['redirect_uri']);
        $this->assertArrayNotHasKey('client_secret', $body);
        // No authorize() call in this process => the verifier is unknown and sent empty (as in 1.x).
        $this->assertSame('', $body['code_verifier']);

        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertSame('Basic ' . base64_encode(self::CLIENT_ID . ':'), $request->getHeaderLine('Authorization'));
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));

        $saved = json_decode($this->tokenStorage->getToken(), true);
        $this->assertSame('initial_access_token', $saved['access_token']);
        $this->assertSame('initial_refresh_token', $saved['refresh_token']);
        $this->assertEqualsWithDelta(time() + 3600, $saved['expires'], 5);
    }

    public function testFinishAuthorizeMapsTokenEndpointErrorToTokenException(): void {
        $this->http->willReturnJson(['error' => 'invalid_grant'], 400, 'POST', 'oauth2/access_token');

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('unable to obtain access_token: invalid_grant');
        $this->api()->finishAuthorize('bad_code');
    }

    public function testFinishAuthorizeMapsTransportFailureToConnectionException(): void {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willThrowException(
            new class ('network down') extends \RuntimeException implements ClientExceptionInterface {}
        );

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('network down');
        $this->api($client)->finishAuthorize('code');
    }

    public function testNonExpiredAccessTokenAttemptsPing(): void {
        $this->tokenStorage->saveToken($this->legacyToken(time(), 3600));
        $this->http->willReturnJson(['data' => ['pong' => 'merchant_id']], 200, 'GET', 'ping');

        $this->assertSame(['data' => ['pong' => 'merchant_id']], $this->api()->ping());

        $request = $this->http->getLastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame(self::BASE_URI . '/v1/ping', (string) $request->getUri());
        $this->assertSame('Bearer ' . self::ACCESS_TOKEN, $request->getHeaderLine('Authorization'));
    }

    public function testLegacyTokenIsMigratedInPlaceWithoutRefresh(): void {
        $issuedAt = time() - 600;
        $this->tokenStorage->saveToken($this->legacyToken($issuedAt, 3600));
        $this->http->willReturnJson(['data' => []], 200, 'GET', 'ping');

        $this->api()->ping();

        // Exactly one request was made: the ping. No token refresh happened.
        $this->assertCount(1, $this->http->getRecordedRequests());

        $saved = json_decode($this->tokenStorage->getToken(), true);
        $this->assertSame(self::ACCESS_TOKEN, $saved['access_token']);
        $this->assertSame('test_refresh_token', $saved['refresh_token']);
        $this->assertSame($issuedAt + 3600, $saved['expires']);
        $this->assertArrayNotHasKey('issued_at', $saved);
        $this->assertArrayNotHasKey('expires_in', $saved);
        $this->assertArrayNotHasKey('provider_id', $saved);
    }

    public function testExpiredAccessTokenAttemptsTokenRefresh(): void {
        $this->tokenStorage->saveToken($this->legacyToken(time() - 3600, 1));
        $this->http->willReturnJson($this->tokenResponse('new_access_token', 'new_refresh_token'), 200, 'POST', 'oauth2/access_token');
        $this->http->willReturnJson(['data' => []], 200, 'GET', 'ping');

        $this->assertTrue($this->api()->isAuthorized());

        [$refresh, $ping] = $this->http->getRecordedRequests();
        $this->assertSame('POST', $refresh->getMethod());
        $this->assertSame(self::BASE_URI . '/oauth2/access_token', (string) $refresh->getUri());
        parse_str((string) $refresh->getBody(), $body);
        $this->assertSame('refresh_token', $body['grant_type']);
        $this->assertSame('test_refresh_token', $body['refresh_token']);
        $this->assertSame('full', $body['scope']);
        $this->assertSame('application/json', $refresh->getHeaderLine('Accept'));
        $this->assertSame('Basic ' . base64_encode(self::CLIENT_ID . ':'), $refresh->getHeaderLine('Authorization'));
        $this->assertSame('application/x-www-form-urlencoded', $refresh->getHeaderLine('Content-Type'));

        // The ping carries the freshly issued token.
        $this->assertSame('Bearer new_access_token', $ping->getHeaderLine('Authorization'));
    }

    public function testExpiredAccessTokenAttemptsToSaveToken(): void {
        $this->tokenStorage->saveToken($this->legacyToken(time() - 3600, 1));
        $this->http->willReturnJson($this->tokenResponse('new_access_token', 'new_refresh_token'), 200, 'POST', 'oauth2/access_token');
        $this->http->willReturnJson(['data' => []], 200, 'GET', 'ping');

        $this->api()->ping();

        $saved = json_decode($this->tokenStorage->getToken(), true);
        $this->assertSame('new_access_token', $saved['access_token']);
        $this->assertSame('new_refresh_token', $saved['refresh_token']);
    }

    public function testRefreshResponseWithoutRefreshTokenKeepsTheCurrentOne(): void {
        $this->tokenStorage->saveToken($this->legacyToken(time() - 3600, 1));
        $this->http->willReturnJson($this->tokenResponse('new_access_token', null), 200, 'POST', 'oauth2/access_token');
        $this->http->willReturnJson(['data' => []], 200, 'GET', 'ping');

        $this->api()->ping();

        $saved = json_decode($this->tokenStorage->getToken(), true);
        $this->assertSame('new_access_token', $saved['access_token']);
        $this->assertSame('test_refresh_token', $saved['refresh_token']);
    }

    public function testDeniedAccessTokenReturnsFalse(): void {
        $this->tokenStorage->saveToken($this->legacyToken(time(), 3600));
        $this->http->willReturn(new \GuzzleHttp\Psr7\Response(401), 'GET', 'ping');

        $this->assertFalse($this->api()->isAuthorized());
    }

    public function testMissingTokenReturnsFalse(): void {
        $this->assertFalse($this->api()->isAuthorized());
    }

    public function testMissingRefreshTokenReturnsFalse(): void {
        $this->tokenStorage->saveToken($this->legacyToken(time() - 3600, 1, refreshToken: null));

        $this->assertFalse($this->api()->isAuthorized());
    }

    public function testInvalidGrantRefreshTokenReturnsFalse(): void {
        $this->tokenStorage->saveToken($this->legacyToken(time() - 3600, 1));
        $this->http->willReturnJson(['error' => 'invalid_grant'], 400, 'POST', 'oauth2/access_token');

        $this->assertFalse($this->api()->isAuthorized());
    }

    public function testInvalidRefreshTokenResponseThrowsException(): void {
        $this->tokenStorage->saveToken($this->legacyToken(time() - 3600, 1));
        $this->http->willReturnJson([], 400, 'POST', 'oauth2/access_token');

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('unable to refresh access_token: token endpoint responded HTTP 400');
        $this->api()->ping();
    }

    public function testRefreshResponseWithoutAccessTokenThrowsException(): void {
        $this->tokenStorage->saveToken($this->legacyToken(time() - 3600, 1));
        $this->http->willReturnJson(['token_type' => 'Bearer'], 200, 'POST', 'oauth2/access_token');

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('unable to refresh access_token: token endpoint response is missing "access_token"');
        $this->api()->ping();
    }

    public function testNonJsonRefreshResponseThrowsException(): void {
        $this->tokenStorage->saveToken($this->legacyToken(time() - 3600, 1));
        $this->http->willReturn(new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'text/html'], '<html>'), 'POST', 'oauth2/access_token');

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('unable to refresh access_token: token endpoint response is missing "access_token"');
        $this->api()->ping();
    }

    public function testRefreshTransportFailureBecomesConnectionException(): void {
        $this->tokenStorage->saveToken($this->legacyToken(time() - 3600, 1));
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willThrowException(
            new class ('network down') extends \RuntimeException implements ClientExceptionInterface {}
        );

        $this->expectException(ConnectionException::class);
        $this->api($client)->ping();
    }

    private function api(?ClientInterface $client = null): OnPayAPI {
        if (null === $client) {
            return $this->createApi([], $this->tokenStorage);
        }

        $factory = new \GuzzleHttp\Psr7\HttpFactory();

        return new OnPayAPI($this->tokenStorage, [
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT_URI,
            'base_uri' => self::BASE_URI,
            'base_authorize_uri' => self::BASE_AUTHORIZE_URI,
        ], $client, $factory, $factory, $this->logger);
    }

    /**
     * @return array<string,mixed>
     */
    private function tokenResponse(string $accessToken, ?string $refreshToken): array {
        $response = [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'full',
        ];
        if (null !== $refreshToken) {
            $response['refresh_token'] = $refreshToken;
        }

        return $response;
    }

    /**
     * A token as persisted by SDK 1.x AccessToken::toJson(): same key order, and
     * absent values written as null rather than omitted.
     */
    private function legacyToken(int $issuedAt, int $expiresIn, ?string $refreshToken = 'test_refresh_token'): string {
        return json_encode([
            'provider_id' => self::BASE_AUTHORIZE_URI . '/oauth2/authorize|' . self::CLIENT_ID,
            'issued_at' => date('Y-m-d H:i:s', $issuedAt),
            'access_token' => self::ACCESS_TOKEN,
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
            'refresh_token' => $refreshToken,
            'scope' => 'full',
        ]);
    }
}
