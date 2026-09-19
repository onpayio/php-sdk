<?php

namespace Tests\Unit\OAuth;

use OnPay\InternalTokenStorage;
use OnPay\OAuth\Client\Exception\AuthorizeException;
use OnPay\OAuth\Client\Exception\OAuthException;
use OnPay\OAuth\Client\Exception\TokenException;
use OnPay\OAuth\Client\Http\Request;
use OnPay\OAuth\Client\Http\Response;
use OnPay\OAuth\Client\OAuthClient;
use OnPay\OAuth\Client\Provider;
use PHPUnit\Framework\TestCase;
use Tests\Unit\OAuth\Fake\FakeHttpClient;
use Tests\Unit\OAuth\Fake\FakeOnPayTokenStorage;
use Tests\Unit\OAuth\Fake\FakeSession;

class OAuthClientTest extends TestCase
{
    private const AUTH_ENDPOINT = 'https://auth.example.com/authorize';
    private const TOKEN_ENDPOINT = 'https://auth.example.com/token';
    private const CLIENT_ID = 'client-123';
    private const SECRET = 'secret-xyz';
    private const SCOPE = 'full';
    private const USER_ID = 'user-1';

    private function provider(?string $authEndpoint = null): Provider
    {
        return new Provider(
            self::CLIENT_ID,
            self::SECRET,
            $authEndpoint ?? self::AUTH_ENDPOINT,
            self::TOKEN_ENDPOINT
        );
    }

    private function providerId(): string
    {
        return self::AUTH_ENDPOINT . '|' . self::CLIENT_ID;
    }

    private function makeClient(FakeOnPayTokenStorage $storage, FakeHttpClient $http): array
    {
        $internal = new InternalTokenStorage($storage, self::AUTH_ENDPOINT, self::CLIENT_ID, self::SCOPE);
        $client = new OAuthClient($internal, $http);
        $session = new FakeSession();
        $client->setSession($session);

        return [$client, $session];
    }

    /**
     * A stored token JSON (OnPay format: contains provider_id) as InternalTokenStorage expects.
     */
    private function storedToken(int $issuedAtOffsetSeconds, int $expiresIn, string $access = 'the-access', ?string $refresh = 'the-refresh'): string
    {
        $data = [
            'provider_id' => $this->providerId(),
            'issued_at' => \date('Y-m-d H:i:s', \time() + $issuedAtOffsetSeconds),
            'access_token' => $access,
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
            'scope' => self::SCOPE,
        ];
        if (null !== $refresh) {
            $data['refresh_token'] = $refresh;
        }

        return \json_encode($data);
    }

    private function jsonResponse(int $status, array $data): Response
    {
        return new Response($status, \json_encode($data), ['Content-Type' => 'application/json']);
    }

    private function basicAuthHeader(): string
    {
        return 'Basic ' . \base64_encode(self::CLIENT_ID . ':' . self::SECRET);
    }

    // --- getAuthorizeUri -----------------------------------------------------

    public function testGetAuthorizeUriBuildsExpectedQueryAndStoresSession(): void
    {
        [$client, $session] = $this->makeClient(new FakeOnPayTokenStorage(), new FakeHttpClient());

        $uri = $client->getAuthorizeUri($this->provider(), self::USER_ID, self::SCOPE, 'https://app.example.com/callback');

        $this->assertStringStartsWith(self::AUTH_ENDPOINT . '?', $uri);

        \parse_str(\substr($uri, \strlen(self::AUTH_ENDPOINT) + 1), $query);
        $this->assertSame(self::CLIENT_ID, $query['client_id']);
        $this->assertSame('https://app.example.com/callback', $query['redirect_uri']);
        $this->assertSame(self::SCOPE, $query['scope']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertNotEmpty($query['state']);
        $this->assertNotEmpty($query['code_challenge']);

        // The randomly-generated state MUST be persisted for later CSRF validation.
        $stored = $session->values['_oauth2_session'];
        $this->assertSame($query['state'], $stored['state']);
        $this->assertSame(self::USER_ID, $stored['user_id']);
        $this->assertSame($this->providerId(), $stored['provider_id']);
        $this->assertNotEmpty($stored['code_verifier']);
        $this->assertSame(self::SCOPE, $stored['scope']);
        $this->assertSame('https://app.example.com/callback', $stored['redirect_uri']);
    }

    public function testGetAuthorizeUriUsesAmpersandWhenEndpointAlreadyHasQuery(): void
    {
        [$client] = $this->makeClient(new FakeOnPayTokenStorage(), new FakeHttpClient());

        $endpoint = self::AUTH_ENDPOINT . '?foo=bar';
        $uri = $client->getAuthorizeUri($this->provider($endpoint), self::USER_ID, self::SCOPE, 'https://app.example.com/callback');

        $this->assertStringStartsWith($endpoint . '&', $uri);
        $this->assertStringContainsString('foo=bar&client_id=' . self::CLIENT_ID, $uri);
    }

    // --- handleCallback: error / validation branches -------------------------

    public function testHandleCallbackThrowsAuthorizeExceptionOnErrorWithDescription(): void
    {
        [$client, $session] = $this->makeClient(new FakeOnPayTokenStorage(), new FakeHttpClient());
        $session->set('_oauth2_session', ['state' => 'x']);

        try {
            $client->handleCallback($this->provider(), self::USER_ID, [
                'error' => 'access_denied',
                'error_description' => 'the user said no',
            ]);
            $this->fail('expected AuthorizeException');
        } catch (AuthorizeException $e) {
            $this->assertSame('access_denied', $e->getMessage());
            $this->assertSame('the user said no', $e->getDescription());
        }

        // the error branch removes the stored session before throwing
        $this->assertArrayNotHasKey('_oauth2_session', $session->values);
    }

    public function testHandleCallbackAuthorizeExceptionHasNullDescriptionWhenAbsent(): void
    {
        [$client] = $this->makeClient(new FakeOnPayTokenStorage(), new FakeHttpClient());

        try {
            $client->handleCallback($this->provider(), self::USER_ID, ['error' => 'server_error']);
            $this->fail('expected AuthorizeException');
        } catch (AuthorizeException $e) {
            $this->assertSame('server_error', $e->getMessage());
            $this->assertNull($e->getDescription());
        }
    }

    public function testHandleCallbackNonStringErrorFallsBackToGenericMessage(): void
    {
        [$client] = $this->makeClient(new FakeOnPayTokenStorage(), new FakeHttpClient());

        try {
            $client->handleCallback($this->provider(), self::USER_ID, ['error' => ['access_denied']]);
            $this->fail('expected AuthorizeException');
        } catch (AuthorizeException $e) {
            $this->assertSame('authorization error', $e->getMessage());
            $this->assertNull($e->getDescription());
        }
    }

    public function testHandleCallbackThrowsWhenCodeMissing(): void
    {
        [$client] = $this->makeClient(new FakeOnPayTokenStorage(), new FakeHttpClient());

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('missing "code" query parameter from server response');
        $client->handleCallback($this->provider(), self::USER_ID, ['state' => 'x']);
    }

    public function testHandleCallbackThrowsWhenStateMissing(): void
    {
        [$client] = $this->makeClient(new FakeOnPayTokenStorage(), new FakeHttpClient());

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('missing "state" query parameter from server response');
        $client->handleCallback($this->provider(), self::USER_ID, ['code' => 'x']);
    }

    private function seedValidSession(FakeSession $session, array $overrides = []): void
    {
        $session->set('_oauth2_session', \array_merge([
            'state' => 'the-state',
            'provider_id' => $this->providerId(),
            'user_id' => self::USER_ID,
            'redirect_uri' => 'https://app.example.com/callback',
            'code_verifier' => 'the-verifier',
            'scope' => self::SCOPE,
        ], $overrides));
    }

    public function testHandleCallbackStateMismatchThrows(): void
    {
        [$client, $session] = $this->makeClient(new FakeOnPayTokenStorage(), new FakeHttpClient());
        $this->seedValidSession($session, ['state' => 'the-real-state']);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('invalid session (state)');
        $client->handleCallback($this->provider(), self::USER_ID, ['code' => 'c', 'state' => 'a-different-state']);
    }

    public function testHandleCallbackProviderMismatchThrows(): void
    {
        [$client, $session] = $this->makeClient(new FakeOnPayTokenStorage(), new FakeHttpClient());
        $this->seedValidSession($session, ['state' => 'the-state', 'provider_id' => 'some-other-provider|id']);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('invalid session (provider_id)');
        $client->handleCallback($this->provider(), self::USER_ID, ['code' => 'c', 'state' => 'the-state']);
    }

    public function testHandleCallbackUserMismatchThrows(): void
    {
        [$client, $session] = $this->makeClient(new FakeOnPayTokenStorage(), new FakeHttpClient());
        $this->seedValidSession($session, ['state' => 'the-state', 'user_id' => 'another-user']);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('invalid session (user_id)');
        $client->handleCallback($this->provider(), self::USER_ID, ['code' => 'c', 'state' => 'the-state']);
    }

    // --- handleCallback: success + token request construction ----------------

    public function testHandleCallbackSuccessStoresTokenAndBuildsTokenRequest(): void
    {
        $storage = new FakeOnPayTokenStorage();
        $http = new FakeHttpClient([
            $this->jsonResponse(200, [
                'access_token' => 'server-access-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'refresh_token' => 'server-refresh-token',
                'scope' => self::SCOPE,
            ]),
        ]);
        [$client, $session] = $this->makeClient($storage, $http);
        $this->seedValidSession($session, ['state' => 'the-state', 'code_verifier' => 'verifier-abc']);

        $client->handleCallback($this->provider(), self::USER_ID, ['code' => 'the-auth-code', 'state' => 'the-state']);

        $request = $http->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(self::TOKEN_ENDPOINT, $request->getUri());
        $this->assertSame(\http_build_query([
            'client_id' => self::CLIENT_ID,
            'grant_type' => 'authorization_code',
            'code' => 'the-auth-code',
            'redirect_uri' => 'https://app.example.com/callback',
            'code_verifier' => 'verifier-abc',
        ], '&'), $request->getBody());
        $this->assertSame([
            'Accept' => 'application/json',
            'Authorization' => $this->basicAuthHeader(),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], $request->getHeaders());

        // token was persisted
        $saved = \json_decode($storage->getLastSaved(), true);
        $this->assertSame('server-access-token', $saved['access_token']);
        $this->assertSame('server-refresh-token', $saved['refresh_token']);
        $this->assertSame($this->providerId(), $saved['provider_id']);
        $this->assertSame(self::SCOPE, $saved['scope']);
    }

    public function testHandleCallbackThrowsTokenExceptionWhenTokenEndpointFails(): void
    {
        $http = new FakeHttpClient([new Response(400, 'nope', [])]);
        [$client, $session] = $this->makeClient(new FakeOnPayTokenStorage(), $http);
        $this->seedValidSession($session, ['state' => 'the-state']);

        try {
            $client->handleCallback($this->provider(), self::USER_ID, ['code' => 'c', 'state' => 'the-state']);
            $this->fail('expected TokenException');
        } catch (TokenException $e) {
            $this->assertSame('unable to obtain access_token', $e->getMessage());
            $this->assertSame(400, $e->getResponse()->getStatusCode());
        }
    }

    public function testHandleCallbackThrowsWhenSessionDataIsNotAnArray(): void
    {
        [$client, $session] = $this->makeClient(new FakeOnPayTokenStorage(), new FakeHttpClient());
        $session->set('_oauth2_session', 'not-an-array');

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('invalid session (state)');
        $client->handleCallback($this->provider(), self::USER_ID, ['code' => 'c', 'state' => 'the-state']);
    }

    public function testHandleCallbackThrowsWhenTokenResponseBodyIsNotAnArray(): void
    {
        $http = new FakeHttpClient([
            new Response(200, \json_encode('a string'), ['Content-Type' => 'application/json']),
        ]);
        [$client, $session] = $this->makeClient(new FakeOnPayTokenStorage(), $http);
        $this->seedValidSession($session, ['state' => 'the-state']);

        try {
            $client->handleCallback($this->provider(), self::USER_ID, ['code' => 'c', 'state' => 'the-state']);
            $this->fail('expected TokenException');
        } catch (TokenException $e) {
            $this->assertSame('unable to obtain access_token', $e->getMessage());
        }
    }

    // --- send: token lookup / expiry / refresh -------------------------------

    public function testSendReturnsFalseWhenNoAccessTokenAvailable(): void
    {
        $http = new FakeHttpClient();
        [$client] = $this->makeClient(new FakeOnPayTokenStorage(null), $http);

        $result = $client->send($this->provider(), self::USER_ID, self::SCOPE, Request::get('https://api.example.com/resource'));

        $this->assertFalse($result);
        $this->assertCount(0, $http->requests);
    }

    public function testSendReturnsFalseWhenStoredTokenProviderIdDoesNotMatch(): void
    {
        // token exists but belongs to a different provider -> skipped in getAccessToken
        $token = \json_encode([
            'provider_id' => 'some-other-provider|other-client',
            'issued_at' => \date('Y-m-d H:i:s'),
            'access_token' => 'foreign-access',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => self::SCOPE,
        ]);
        $http = new FakeHttpClient();
        [$client] = $this->makeClient(new FakeOnPayTokenStorage($token), $http);

        $result = $client->send($this->provider(), self::USER_ID, self::SCOPE, Request::get('https://api.example.com/resource'));

        $this->assertFalse($result);
        $this->assertCount(0, $http->requests);
    }

    public function testSendReturnsFalseWhenStoredTokenScopeDoesNotMatch(): void
    {
        // token matches provider but has a different scope -> skipped in getAccessToken
        $token = \json_encode([
            'provider_id' => $this->providerId(),
            'issued_at' => \date('Y-m-d H:i:s'),
            'access_token' => 'other-scope-access',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'some-other-scope',
        ]);
        $http = new FakeHttpClient();
        [$client] = $this->makeClient(new FakeOnPayTokenStorage($token), $http);

        $result = $client->send($this->provider(), self::USER_ID, self::SCOPE, Request::get('https://api.example.com/resource'));

        $this->assertFalse($result);
        $this->assertCount(0, $http->requests);
    }

    public function testSendReturnsFalseWhenExpiredAndNoRefreshToken(): void
    {
        $http = new FakeHttpClient();
        $storage = new FakeOnPayTokenStorage($this->storedToken(-7200, 1, 'expired-access', null));
        [$client] = $this->makeClient($storage, $http);

        $result = $client->send($this->provider(), self::USER_ID, self::SCOPE, Request::get('https://api.example.com/resource'));

        $this->assertFalse($result);
        $this->assertCount(0, $http->requests);
    }

    public function testSendRefreshesExpiredTokenAndRetriesRequest(): void
    {
        $storage = new FakeOnPayTokenStorage($this->storedToken(-7200, 1, 'expired-access', 'old-refresh'));
        $http = new FakeHttpClient([
            // refresh response
            $this->jsonResponse(200, ['access_token' => 'new-access', 'token_type' => 'Bearer']),
            // actual resource response
            new Response(200, 'resource-ok', []),
        ]);
        [$client] = $this->makeClient($storage, $http);

        $response = $client->send($this->provider(), self::USER_ID, self::SCOPE, Request::get('https://api.example.com/resource'));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('resource-ok', $response->getBody());

        $this->assertCount(2, $http->requests);

        // first request: the refresh_token grant
        $refreshRequest = $http->requests[0];
        $this->assertSame('POST', $refreshRequest->getMethod());
        $this->assertSame(self::TOKEN_ENDPOINT, $refreshRequest->getUri());
        $this->assertSame(\http_build_query([
            'grant_type' => 'refresh_token',
            'refresh_token' => 'old-refresh',
            'scope' => self::SCOPE,
        ], '&'), $refreshRequest->getBody());
        $this->assertSame($this->basicAuthHeader(), $refreshRequest->getHeaders()['Authorization']);

        // second request: the actual resource call carries the refreshed bearer token
        $resourceRequest = $http->requests[1];
        $this->assertSame('Bearer new-access', $resourceRequest->getHeaders()['Authorization']);

        // refreshed token persisted (refresh_token borrowed from old token)
        $saved = \json_decode($storage->getLastSaved(), true);
        $this->assertSame('new-access', $saved['access_token']);
        $this->assertSame('old-refresh', $saved['refresh_token']);
    }

    public function testSendRefreshInvalidGrantReturnsFalse(): void
    {
        $storage = new FakeOnPayTokenStorage($this->storedToken(-7200, 1, 'expired-access', 'old-refresh'));
        $http = new FakeHttpClient([$this->jsonResponse(400, ['error' => 'invalid_grant'])]);
        [$client] = $this->makeClient($storage, $http);

        $result = $client->send($this->provider(), self::USER_ID, self::SCOPE, Request::get('https://api.example.com/resource'));

        $this->assertFalse($result);
        $this->assertCount(1, $http->requests);
    }

    public function testSendRefreshOtherErrorThrowsTokenException(): void
    {
        $storage = new FakeOnPayTokenStorage($this->storedToken(-7200, 1, 'expired-access', 'old-refresh'));
        $http = new FakeHttpClient([$this->jsonResponse(400, [])]);
        [$client] = $this->makeClient($storage, $http);

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('unable to refresh access_token');
        $client->send($this->provider(), self::USER_ID, self::SCOPE, Request::get('https://api.example.com/resource'));
    }

    public function testSendRefreshErrorBodyNotArrayThrowsTokenException(): void
    {
        $storage = new FakeOnPayTokenStorage($this->storedToken(-7200, 1, 'expired-access', 'old-refresh'));
        $http = new FakeHttpClient([
            new Response(400, \json_encode('a string'), ['Content-Type' => 'application/json']),
        ]);
        [$client] = $this->makeClient($storage, $http);

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('unable to refresh access_token');
        $client->send($this->provider(), self::USER_ID, self::SCOPE, Request::get('https://api.example.com/resource'));
    }

    public function testSendRefreshSuccessBodyNotArrayThrowsTokenException(): void
    {
        $storage = new FakeOnPayTokenStorage($this->storedToken(-7200, 1, 'expired-access', 'old-refresh'));
        $http = new FakeHttpClient([
            new Response(200, \json_encode('a string'), ['Content-Type' => 'application/json']),
        ]);
        [$client] = $this->makeClient($storage, $http);

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('unable to refresh access_token');
        $client->send($this->provider(), self::USER_ID, self::SCOPE, Request::get('https://api.example.com/resource'));
    }

    public function testSendReturnsFalseOn401(): void
    {
        $storage = new FakeOnPayTokenStorage($this->storedToken(0, 3600, 'live-access'));
        $http = new FakeHttpClient([new Response(401, '', [])]);
        [$client] = $this->makeClient($storage, $http);

        $result = $client->send($this->provider(), self::USER_ID, self::SCOPE, Request::get('https://api.example.com/resource'));

        $this->assertFalse($result);
        $this->assertCount(1, $http->requests);
    }

    public function testSendReturnsResponseAndSetsBearerHeaderOnSuccess(): void
    {
        $storage = new FakeOnPayTokenStorage($this->storedToken(0, 3600, 'live-access'));
        $http = new FakeHttpClient([new Response(200, 'ok', [])]);
        [$client] = $this->makeClient($storage, $http);

        $response = $client->send($this->provider(), self::USER_ID, self::SCOPE, Request::get('https://api.example.com/resource'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Bearer live-access', $http->getLastRequest()->getHeaders()['Authorization']);
    }

    // --- get()/post() convenience wrappers -----------------------------------

    public function testGetWrapperMisplacesHeadersIntoQueryString(): void
    {
        $storage = new FakeOnPayTokenStorage($this->storedToken(0, 3600, 'live-access'));
        $http = new FakeHttpClient([new Response(200, 'ok', [])]);
        [$client] = $this->makeClient($storage, $http);

        $client->get($this->provider(), self::USER_ID, self::SCOPE, 'https://api.example.com/resource', ['X-Custom' => 'val']);

        $request = $http->getLastRequest();
        $this->assertSame('GET', $request->getMethod());
        // asserts current behaviour: OAuthClient::get forwards $requestHeaders into Request::get's
        // $queryParameters slot, so the "header" ends up in the URI query string...
        $this->assertStringContainsString('X-Custom=val', $request->getUri());
        // ...and never becomes an actual header (only send()'s Authorization is present)
        $this->assertArrayNotHasKey('X-Custom', $request->getHeaders());
        $this->assertSame('Bearer live-access', $request->getHeaders()['Authorization']);
    }

    public function testGetWrapperWithoutHeadersLeavesUriCleanAndSetsOnlyBearer(): void
    {
        // The way the SDK actually calls it (e.g. ping()): no 5th argument.
        // This is the contract the 6330 league swap must preserve.
        $storage = new FakeOnPayTokenStorage($this->storedToken(0, 3600, 'live-access'));
        $http = new FakeHttpClient([new Response(200, 'ok', [])]);
        [$client] = $this->makeClient($storage, $http);

        $client->get($this->provider(), self::USER_ID, self::SCOPE, 'https://api.example.com/resource');

        $request = $http->getLastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://api.example.com/resource', $request->getUri());
        $this->assertSame(['Authorization' => 'Bearer live-access'], $request->getHeaders());
    }

    public function testPostWrapperBuildsFormBodyAndForwardsHeaders(): void
    {
        $storage = new FakeOnPayTokenStorage($this->storedToken(0, 3600, 'live-access'));
        $http = new FakeHttpClient([new Response(200, 'ok', [])]);
        [$client] = $this->makeClient($storage, $http);

        $client->post($this->provider(), self::USER_ID, self::SCOPE, 'https://api.example.com/resource', ['field' => 'value'], ['X-H' => 'h']);

        $request = $http->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://api.example.com/resource', $request->getUri());
        $this->assertSame(\http_build_query(['field' => 'value'], '&'), $request->getBody());
        $headers = $request->getHeaders();
        $this->assertSame('h', $headers['X-H']);
        $this->assertSame('application/x-www-form-urlencoded', $headers['Content-Type']);
        $this->assertSame('Bearer live-access', $headers['Authorization']);
    }
}
