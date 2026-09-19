<?php

namespace Tests\Unit\OAuth;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\OptionProvider\OptionProviderInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use OnPay\Http\GuzzleClientAdapter;
use OnPay\OAuth\OnPayOptionProvider;
use OnPay\OAuth\OnPayProvider;
use OnPay\OAuth\Psr17RequestFactory;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeHttpClient;

class OnPayProviderTest extends TestCase
{
    private const AUTHORIZE_URL = 'https://manage.onpay.invalid/oauth2/authorize';
    private const TOKEN_URL = 'https://api.onpay.invalid/oauth2/access_token';

    private FakeHttpClient $http;

    protected function setUp(): void
    {
        parent::setUp();
        $this->http = new FakeHttpClient();
    }

    public function testEndpointsComeFromOptions(): void
    {
        $provider = $this->provider();

        self::assertSame(self::AUTHORIZE_URL, $provider->getBaseAuthorizationUrl());
        self::assertSame(self::TOKEN_URL, $provider->getBaseAccessTokenUrl([]));
    }

    public function testMissingEndpointOptionsAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Required options "urlAuthorize" and "urlAccessToken" must be strings');
        new OnPayProvider(['clientId' => 'id', 'urlAuthorize' => self::AUTHORIZE_URL]);
    }

    public function testDefaultsToOnPayOptionProviderButAcceptsAnInjectedOne(): void
    {
        self::assertInstanceOf(OnPayOptionProvider::class, $this->provider()->getOptionProvider());

        $custom = $this->createMock(OptionProviderInterface::class);
        $provider = $this->provider(['optionProvider' => $custom]);
        self::assertSame($custom, $provider->getOptionProvider());
    }

    public function testAuthorizationUrlUsesPkceS256AndDefaultScopeWithoutApprovalPrompt(): void
    {
        $provider = $this->provider();

        $url = $provider->getAuthorizationUrl();
        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        self::assertStringStartsWith(self::AUTHORIZE_URL . '?', $url);
        self::assertSame('full', $query['scope']);
        self::assertSame('S256', $query['code_challenge_method']);
        self::assertArrayNotHasKey('approval_prompt', $query);
        self::assertSame($provider->getState(), $query['state']);

        $expectedChallenge = rtrim(strtr(base64_encode(hash('sha256', $provider->getPkceCode(), true)), '+/', '-_'), '=');
        self::assertSame($expectedChallenge, $query['code_challenge']);
    }

    public function testResourceOwnerIsNotSupported(): void
    {
        $provider = $this->provider();
        $token = new AccessToken(['access_token' => 'a']);

        try {
            $provider->getResourceOwnerDetailsUrl($token);
            self::fail('Expected LogicException');
        } catch (\LogicException $e) {
            self::assertSame('OnPay does not expose a resource owner endpoint', $e->getMessage());
        }

        try {
            $provider->getResourceOwner($token);
            self::fail('Expected LogicException');
        } catch (\LogicException $e) {
            self::assertSame('OnPay does not expose a resource owner endpoint', $e->getMessage());
        }

        // Unreachable through getResourceOwner() (the URL lookup throws first), but league
        // requires the hook, so pin its behaviour directly.
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('OnPay does not expose a resource owner endpoint');
        (new \ReflectionMethod($provider, 'createResourceOwner'))->invoke($provider, [], $token);
    }

    public function testSuccessfulTokenResponseIsParsed(): void
    {
        $this->http->willReturnJson(['access_token' => 'a', 'refresh_token' => 'r', 'expires_in' => 60], 200, 'POST', 'oauth2/access_token');

        $token = $this->provider()->getAccessToken('authorization_code', ['code' => 'c']);

        self::assertSame('a', $token->getToken());
        self::assertSame('r', $token->getRefreshToken());
    }

    public function testErrorFieldTakesPrecedenceOverStatus(): void
    {
        $this->http->willReturnJson(['error' => 'invalid_grant'], 200, 'POST', 'oauth2/access_token');

        try {
            $this->provider()->getAccessToken('authorization_code', ['code' => 'c']);
            self::fail('Expected IdentityProviderException');
        } catch (IdentityProviderException $e) {
            self::assertSame('invalid_grant', $e->getMessage());
            self::assertSame(200, $e->getCode());
            self::assertSame(['error' => 'invalid_grant'], $e->getResponseBody());
        }
    }

    public function testErrorStatusWithoutErrorFieldIsReported(): void
    {
        $this->http->willReturnJson(['message' => 'nope'], 503, 'POST', 'oauth2/access_token');

        try {
            $this->provider()->getAccessToken('authorization_code', ['code' => 'c']);
            self::fail('Expected IdentityProviderException');
        } catch (IdentityProviderException $e) {
            self::assertSame('token endpoint responded HTTP 503', $e->getMessage());
            self::assertSame(503, $e->getCode());
        }
    }

    public function testNonJsonSuccessBodyIsReported(): void
    {
        $this->http->willReturn(new Response(200, ['Content-Type' => 'text/plain'], 'ok'), 'POST', 'oauth2/access_token');

        try {
            $this->provider()->getAccessToken('authorization_code', ['code' => 'c']);
            self::fail('Expected IdentityProviderException');
        } catch (IdentityProviderException $e) {
            self::assertSame('token endpoint response is missing "access_token"', $e->getMessage());
            self::assertSame('ok', $e->getResponseBody());
        }
    }

    public function testJsonSuccessBodyWithoutAccessTokenIsReported(): void
    {
        $this->http->willReturnJson(['token_type' => 'Bearer'], 200, 'POST', 'oauth2/access_token');

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('token endpoint response is missing "access_token"');
        $this->provider()->getAccessToken('authorization_code', ['code' => 'c']);
    }

    /**
     * @param array<string,mixed> $collaborators
     */
    private function provider(array $collaborators = []): OnPayProvider
    {
        $factory = new HttpFactory();

        return new OnPayProvider(
            [
                'clientId' => 'test_client_id',
                'redirectUri' => 'https://example.test/redirect',
                'urlAuthorize' => self::AUTHORIZE_URL,
                'urlAccessToken' => self::TOKEN_URL,
            ],
            $collaborators + [
                'httpClient' => new GuzzleClientAdapter($this->http),
                'requestFactory' => new Psr17RequestFactory($factory, $factory),
            ]
        );
    }
}
