<?php

namespace Tests\Unit;

use OnPay\OAuth\Client\Http\Request;
use OnPay\OAuth\Client\Http\Response;
use OnPay\API\Exception\TokenException;
use OnPay\CurlHttpClientLogger;
use OnPay\OnPayAPI;
use OnPay\TokenStorageInterface;
use PHPUnit\Framework\TestCase;

class Oauth2ClientTest extends TestCase {
    protected TokenStorageInterface $tokenStorage;
    protected OnPayAPI $onPayAPI;
    protected CurlHttpClientLogger $httpClient;
    protected Request $lastHttpRequest;

    protected string $clientId = 'test_client_id';
    protected string $baseUri = 'test_base_uri';
    protected string $baseAuthUri = 'test_base_authorize_uri';
    protected string $redirectUri = 'test_redirect_uri';

    public function setUp(): void {
        parent::setUp();

        // Construct API
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->onPayAPI = new OnPayAPI($this->tokenStorage, [
            'base_uri' => $this->baseUri,
            'base_authorize_uri' => $this->baseAuthUri,
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
        ]);

        // Construct http client, and inject into API, allowing us to intercept requests sent.
        $this->httpClient = $this->getMockBuilder(CurlHttpClientLogger::class)
            ->setConstructorArgs([
                ['allowHttp']
            ])
            ->getMock();
        $reflectedApi = new \ReflectionClass($this->onPayAPI);
        $client = $reflectedApi->getProperty('httpClient');
        $client->setAccessible(true);
        $client->setValue($this->onPayAPI, $this->httpClient);
    }


    public function testAuthorizeUrlIsExpectedFormat(): void {
        // Get Auth URL
        $url = $this->onPayAPI->authorize();

        // Validate that the returned Auth URL is as expected
        $expectedPath = $this->baseAuthUri . '/oauth2/authorize';
        $this->assertStringContainsString($expectedPath, $url);
        parse_str(str_replace($expectedPath . '?', '', $url), $urlQueryArr);

        // Validate that URL contains all required parameters
        $this->assertArrayHasKey('client_id', $urlQueryArr);
        $this->assertArrayHasKey('redirect_uri', $urlQueryArr);
        $this->assertArrayHasKey('scope', $urlQueryArr);
        $this->assertArrayHasKey('state', $urlQueryArr);
        $this->assertArrayHasKey('response_type', $urlQueryArr);
        $this->assertArrayHasKey('code_challenge_method', $urlQueryArr);
        $this->assertArrayHasKey('code_challenge', $urlQueryArr);

        // Validate that values we can calculate are as expected
        // State and Code challenge are based on a random value in the oauth2 client
        $this->assertEquals($this->clientId, $urlQueryArr['client_id']);
        $this->assertEquals($this->redirectUri, $urlQueryArr['redirect_uri']);
        $this->assertEquals('full', $urlQueryArr['scope']);
        $this->assertEquals('code', $urlQueryArr['response_type']);
        $this->assertEquals('S256', $urlQueryArr['code_challenge_method']);
    }

    public function testFinishAuthorize() {
        // Intercept the value of the token being saved
        $lastSavedToken = null;
        $this->tokenStorage->method('saveToken')->willReturnCallback(function($token) use (&$lastSavedToken) {
            $lastSavedToken = json_decode($token, true);
        });

        // Intercept request made to the API
        $testCase = $this;
        $lastRequest = $this->createMock(Request::class);
        $this->httpClient->method('send')->willReturnCallback(function(Request $request) use (&$lastRequest, $testCase)  {
            $lastRequest = $request;
            // Construct a valid refresh token response with initial refresh and access tokens
            return new Response(200, $testCase->getToken(time(), 3600, 'initial_test_access_token', 'initial_test_refresh_token'), ['Content-Type' => 'application/json']);
        });

        // Perform finish authorize with test code
        $this->onPayAPI->finishAuthorize('test_finish_code');

        // Validate that the request is a POST request
        $this->assertEquals('POST', $lastRequest->getMethod());
        // Validate if the correct API endpoint is requested
        $this->assertEquals($this->baseUri . '/oauth2/access_token', $lastRequest->getUri());
        // Validate the post body
        $this->assertEquals(http_build_query([
            'client_id' => $this->clientId,
            'grant_type' => 'authorization_code',
            'code' => 'test_finish_code',
            'redirect_uri' => $this->redirectUri,
            'code_verifier' => ''
        ]), $lastRequest->getBody());
        // Validate that the correct header is sent to the API
        $this->assertEquals([
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':'),
            'Content-Type' => 'application/x-www-form-urlencoded'
        ], $lastRequest->getHeaders());

        // Validate that the saved token is the initial token provided by API
        $this->assertNotEquals(null, $lastSavedToken);
        $this->assertArrayHasKey('access_token', $lastSavedToken);
        $this->assertEquals('initial_test_access_token', $lastSavedToken['access_token']);
        $this->assertArrayHasKey('refresh_token', $lastSavedToken);
        $this->assertEquals('initial_test_refresh_token', $lastSavedToken['refresh_token']);
    }

    public function testNonExpiredAccessTokenAttemptsPing(): void {
        // Construct non-expired OAUTH2 token
        $this->tokenStorage->method('getToken')->willReturn($this->getToken(time(), 3600));

        // Intercept request made to the API
        $lastRequest = $this->createMock(Request::class);
        $this->httpClient->method('send')->willReturnCallback(function(Request $request) use (&$lastRequest)  {
            $lastRequest = $request;
            return new Response(200, json_encode([
                'data' => [
                    'pong' => 'merchant_id'
                ]
            ])); // Report that everything is OK
        });

        // Perform API action that checks if authorized. Performs ping to API.
        $authorized = $this->onPayAPI->isAuthorized();
        // Validate that the request returned true
        $this->assertEquals(true, $authorized);
        // Validate that the request is a GET request
        $this->assertEquals('GET', $lastRequest->getMethod());
        // Validate if the correct API endpoint is requested
        $this->assertEquals($this->baseUri . '/v1/ping', $lastRequest->getUri());
        // Validate that the correct authorization is sent to the API
        $this->assertEquals([
            'Authorization' => 'Bearer test_access_token',
            'User-Agent' => $this->onPayAPI->getPlatform()
        ], $lastRequest->getHeaders());
    }

    public function testExpiredAccessTokenAttemptsTokenRefresh(): void {
        // Construct token that will cause the OAUTH2 client to attempt a renewal
        $this->tokenStorage->method('getToken')->willReturn($this->getToken(time() - 3600, 1));

        // Intercept request made to the API
        $lastRequest = $this->createMock(Request::class);
        $this->httpClient->method('send')->willReturnCallback(function(Request $request) use (&$lastRequest)  {
            $lastRequest = $request;
            // Construct a valid refresh token response with new refresh and access tokens
            return new Response(200, '{}', ['Content-Type' => 'application/json']);
        });

        // Perform API action that checks if authorized. Performs ping to API.
        $this->onPayAPI->isAuthorized();
        // Validate that the request is a POST request
        $this->assertEquals('POST', $lastRequest->getMethod());
        // Validate if the correct API endpoint is requested
        $this->assertEquals($this->baseUri . '/oauth2/access_token', $lastRequest->getUri());
        // Validate the post body
        $this->assertEquals(http_build_query([
            'grant_type' => 'refresh_token',
            'refresh_token' => 'test_refresh_token',
            'scope' => 'full',
        ]), $lastRequest->getBody());
        // Validate that the correct authorization is sent to the API
        $this->assertEquals([
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':'),
            'Content-Type' => 'application/x-www-form-urlencoded'
        ], $lastRequest->getHeaders());
    }

    public function testExpiredAccessTokenAttemptsToSaveToken(): void {
        // Construct token that will cause the OAUTH2 client to attempt a renewal
        $this->tokenStorage->method('getToken')->willReturn($this->getToken(time() - 3600, 1));

        // Intercept the value of the token being saved
        $lastSavedToken = null;
        $this->tokenStorage->method('saveToken')->willReturnCallback(function($token) use (&$lastSavedToken) {
            $lastSavedToken = json_decode($token, true);
        });

        // Intercept request made to the API
        $testCase = $this;
        $this->httpClient->method('send')->willReturnCallback(function(Request $request) use ($testCase)  {
            // Construct a valid refresh token response with new refresh and access tokens
            return new Response(200, $testCase->getToken(time(), 3600, 'new_test_access_token', 'new_test_refresh_token'), ['Content-Type' => 'application/json']);
        });

        // Perform ping to API. Will attempt to auth.
        $this->onPayAPI->ping();

        // Validate that the saved token is the new token provided by API
        $this->assertNotEquals(null, $lastSavedToken);
        $this->assertArrayHasKey('access_token', $lastSavedToken);
        $this->assertEquals('new_test_access_token', $lastSavedToken['access_token']);
        $this->assertArrayHasKey('refresh_token', $lastSavedToken);
        $this->assertEquals('new_test_refresh_token', $lastSavedToken['refresh_token']);
    }

    public function testDeniedAccessTokenReturnsFalse(): void {
        // Construct non-expired OAUTH2 token
        $this->tokenStorage->method('getToken')->willReturn($this->getToken(time(), 3600));

        // Intercept request made to the API
        $this->httpClient->method('send')->willReturnCallback(function(Request $request) {
            // Deny the request
            return new Response(401, '');
        });

        // Perform API action that checks if authorized. Performs ping to API.
        $authorized = $this->onPayAPI->isAuthorized();

        // Validate that API is not authorized
        $this->assertEquals(false, $authorized);
    }

    public function testMissingRefreshTokenReturnsFalse(): void {
        // Construct token that will cause the OAUTH2 client to attempt a renewal
        // No refresh token will be present in stored token
        $this->tokenStorage->method('getToken')->willReturn($this->getToken(time() - 3600, 1, refreshToken: ''));

        // Perform API action that checks if authorized. Performs ping to API.
        $authorized = $this->onPayAPI->isAuthorized();

        // Validate that API is not authorized
        $this->assertEquals(false, $authorized);
    }

    public function testInvalidGrantRefreshTokenReturnsFalse(): void {
        // Construct token that will cause the OAUTH2 client to attempt a renewal
        $this->tokenStorage->method('getToken')->willReturn($this->getToken(time() - 3600, 1));

        // Intercept request made to the API
        $this->httpClient->method('send')->willReturnCallback(function(Request $request) {
            // Return invalid grant response
            return new Response(400, json_encode([
                'error' => 'invalid_grant'
            ]), [
                'Content-Type' => 'application/json'
            ]);
        });

        // Perform API action that checks if authorized. Performs ping to API.
        $authorized = $this->onPayAPI->isAuthorized();

        // Validate that API is not authorized
        $this->assertEquals(false, $authorized);
    }

    public function testInvalidRefreshTokenResponseThrowsException(): void {
        // Construct token that will cause the OAUTH2 client to attempt a renewal
        $this->tokenStorage->method('getToken')->willReturn($this->getToken(time() - 3600, 1));

        // Intercept request made to the API
        $this->httpClient->method('send')->willReturnCallback(function(Request $request) {
            // Deny the request
            return new Response(400, '{}', ['Content-Type' => 'application/json']);
        });

        // We expect a ping will throw an exception now
        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('unable to refresh access_token');

        // Perform ping to API. Will attempt to auth.
        $authorized = $this->onPayAPI->ping();

        // Validate that API is not authorized
        $this->assertEquals(false, $authorized);
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