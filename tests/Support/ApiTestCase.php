<?php

namespace Tests\Support;

use GuzzleHttp\Psr7\HttpFactory;
use OnPay\AuthStateStorageInterface;
use OnPay\OnPayAPI;
use OnPay\TokenStorageInterface;
use PHPUnit\Framework\TestCase;

/**
 * Base test case for harness tests that drive the real OnPayAPI end-to-end against a
 * {@see FakeHttpClient} — no network.
 *
 * The base_uri points at an unroutable host so that a test which forgets to inject the
 * fake (and would otherwise auto-discover the installed Guzzle client and hit the real
 * network) fails fast instead.
 */
abstract class ApiTestCase extends TestCase
{
    protected const CLIENT_ID = 'test_client_id';
    protected const BASE_URI = 'https://api.onpay.invalid';
    protected const BASE_AUTHORIZE_URI = 'https://manage.onpay.invalid';
    protected const REDIRECT_URI = 'https://example.test/redirect';
    protected const ACCESS_TOKEN = 'test_access_token';

    protected FakeHttpClient $http;

    protected RecordingLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->http = new FakeHttpClient();
        $this->logger = new RecordingLogger();
    }

    protected function tearDown(): void
    {
        // An arranged-but-unused fixture is a mistake: fail the test.
        $this->http->assertAllResponsesConsumed();
        parent::tearDown();
    }

    /**
     * Build a real OnPayAPI wired to the fake HTTP client, explicit PSR-17 factories and
     * the in-memory {@see RecordingLogger} (so nothing reaches error_log() during tests).
     *
     * @param array<string,mixed> $options extra/overriding OnPayAPI options
     */
    protected function createApi(
        array $options = [],
        ?TokenStorageInterface $tokenStorage = null,
        ?AuthStateStorageInterface $authStateStorage = null
    ): OnPayAPI {
        // Explicit factories: never rely on Psr17FactoryDiscovery, which would make the
        // resolved stack environment-dependent.
        $factory = new HttpFactory();

        $options = array_merge([
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT_URI,
            'base_uri' => self::BASE_URI,
            'base_authorize_uri' => self::BASE_AUTHORIZE_URI,
        ], $options);

        return new OnPayAPI(
            $tokenStorage ?? $this->validTokenStorage(),
            $options,
            $this->http,
            $factory,
            $factory,
            $this->logger,
            $authStateStorage
        );
    }

    protected function validTokenStorage(): TokenStorageInterface
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($this->validToken());

        return $tokenStorage;
    }

    /**
     * A fresh, non-expired token whose provider_id matches the provider OnPayAPI builds
     * from the options above, so the OAuth client attaches the Bearer and makes exactly
     * one request (no token-refresh round-trip).
     */
    protected function validToken(int $expiresIn = 3600): string
    {
        return json_encode([
            'provider_id' => self::BASE_AUTHORIZE_URI . '/oauth2/authorize|' . self::CLIENT_ID,
            'issued_at' => date('Y-m-d H:i:s', time()),
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
            'scope' => 'full',
            'access_token' => self::ACCESS_TOKEN,
            'refresh_token' => 'test_refresh_token',
        ]);
    }

    /**
     * The Authorization header value every authenticated request should carry.
     */
    protected function expectedAuthorizationHeader(): string
    {
        return 'Bearer ' . self::ACCESS_TOKEN;
    }
}
