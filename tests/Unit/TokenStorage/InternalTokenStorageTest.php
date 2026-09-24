<?php

namespace Tests\Unit\TokenStorage;

use League\OAuth2\Client\Token\AccessToken;
use OnPay\API\Exception\TokenException;
use OnPay\InternalTokenStorage;
use PHPUnit\Framework\TestCase;

/**
 * InternalTokenStorage: the bridge between the consumer's TokenStorageInterface and
 * league's AccessToken, including the transparent upgrade of tokens written by SDK 1.x.
 */
class InternalTokenStorageTest extends TestCase
{
    public function testEmptyStorageYieldsNoToken(): void
    {
        self::assertNull((new InternalTokenStorage(new FakeTokenStorage()))->getAccessToken());
        self::assertNull((new InternalTokenStorage(new FakeTokenStorage('')))->getAccessToken());
    }

    public function testLeagueFormatIsReadBackVerbatim(): void
    {
        $json = json_encode([
            'access_token' => 'access',
            'refresh_token' => 'refresh',
            'expires' => 1_900_000_000,
            'token_type' => 'Bearer',
            'scope' => 'full',
        ]);
        $inner = new FakeTokenStorage($json);

        $token = (new InternalTokenStorage($inner))->getAccessToken();

        self::assertSame('access', $token->getToken());
        self::assertSame('refresh', $token->getRefreshToken());
        self::assertSame(1_900_000_000, $token->getExpires());
        self::assertSame(['token_type' => 'Bearer', 'scope' => 'full'], $token->getValues());
        // Nothing is re-saved when no conversion was needed.
        self::assertSame($json, $inner->getToken());
    }

    public function testLegacyTokenIsConvertedToAbsoluteExpiryAndReSaved(): void
    {
        $issuedAt = time() - 600;
        $inner = new FakeTokenStorage(self::legacyJson([
            'issued_at' => date('Y-m-d H:i:s', $issuedAt),
            'expires_in' => 3600,
        ]));

        $token = (new InternalTokenStorage($inner))->getAccessToken();

        self::assertSame('legacy_access', $token->getToken());
        self::assertSame('legacy_refresh', $token->getRefreshToken());
        // issued_at + expires_in, not "now + expires_in".
        self::assertSame($issuedAt + 3600, $token->getExpires());
        self::assertFalse($token->hasExpired());

        $saved = json_decode($inner->getToken(), true);
        self::assertSame([
            'token_type' => 'Bearer',
            'scope' => 'full',
            'access_token' => 'legacy_access',
            'refresh_token' => 'legacy_refresh',
            'expires' => $issuedAt + 3600,
        ], $saved);
    }

    /**
     * Byte-for-byte what SDK 1.x AccessToken::toJson() writes (escaped slashes,
     * fixed key order, all seven keys always present).
     */
    public function testVerbatim1xTokenIsConverted(): void
    {
        $inner = new FakeTokenStorage(
            '{"provider_id":"https:\/\/manage.onpay.io\/oauth2\/authorize|client_id","issued_at":"2024-05-01 12:00:00",'
            . '"access_token":"legacy_access","token_type":"Bearer","expires_in":3600,"refresh_token":"legacy_refresh","scope":"full"}'
        );

        $token = (new InternalTokenStorage($inner))->getAccessToken();

        self::assertSame('legacy_access', $token->getToken());
        self::assertSame('legacy_refresh', $token->getRefreshToken());
        self::assertSame(strtotime('2024-05-01 12:00:00') + 3600, $token->getExpires());
        self::assertTrue($token->hasExpired());
        self::assertSame(['token_type' => 'Bearer', 'scope' => 'full'], $token->getValues());

        self::assertSame([
            'token_type' => 'Bearer',
            'scope' => 'full',
            'access_token' => 'legacy_access',
            'refresh_token' => 'legacy_refresh',
            'expires' => strtotime('2024-05-01 12:00:00') + 3600,
        ], json_decode($inner->getToken(), true));
    }

    /**
     * 1.x never omits keys: a token without expiry/refresh/scope is written with
     * explicit nulls, which must read as "no expiry, no refresh token".
     */
    public function testVerbatim1xTokenWithNullFieldsIsConverted(): void
    {
        $inner = new FakeTokenStorage(
            '{"provider_id":"https:\/\/manage.onpay.io\/oauth2\/authorize|client_id","issued_at":"2024-05-01 12:00:00",'
            . '"access_token":"legacy_access","token_type":"Bearer","expires_in":null,"refresh_token":null,"scope":null}'
        );

        $token = (new InternalTokenStorage($inner))->getAccessToken();

        self::assertSame('legacy_access', $token->getToken());
        self::assertNull($token->getRefreshToken());
        self::assertNull($token->getExpires());
        self::assertSame(['token_type' => 'Bearer', 'scope' => null], $token->getValues());

        self::assertSame(
            ['token_type' => 'Bearer', 'scope' => null, 'access_token' => 'legacy_access'],
            json_decode($inner->getToken(), true)
        );
    }

    public function testLegacyTokenPastItsExpiryReadsAsExpired(): void
    {
        $issuedAt = time() - 7200;
        $inner = new FakeTokenStorage(self::legacyJson([
            'issued_at' => date('Y-m-d H:i:s', $issuedAt),
            'expires_in' => 3600,
        ]));

        $token = (new InternalTokenStorage($inner))->getAccessToken();

        self::assertSame($issuedAt + 3600, $token->getExpires());
        self::assertTrue($token->hasExpired());
        // The refresh token survives so the API can refresh instead of forcing re-authorization.
        self::assertSame('legacy_refresh', $token->getRefreshToken());
    }

    public function testLegacyTokenWithoutExpiresInNeverExpires(): void
    {
        $inner = new FakeTokenStorage(self::legacyJson([
            'issued_at' => '2020-01-01 00:00:00',
        ]));

        $token = (new InternalTokenStorage($inner))->getAccessToken();

        self::assertNull($token->getExpires());
        self::assertArrayNotHasKey('expires', json_decode($inner->getToken(), true));
    }

    public function testLegacyTokenWithInvalidIssuedAtIsRejected(): void
    {
        $storage = new InternalTokenStorage(new FakeTokenStorage(self::legacyJson([
            'issued_at' => 'not a date',
            'expires_in' => 3600,
        ])));

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('stored token has an invalid "issued_at"');
        $storage->getAccessToken();
    }

    public function testNonJsonTokenIsRejected(): void
    {
        $storage = new InternalTokenStorage(new FakeTokenStorage('not json'));

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('stored token is not valid JSON');
        $storage->getAccessToken();
    }

    public function testTokenWithoutAccessTokenIsRejected(): void
    {
        $storage = new InternalTokenStorage(new FakeTokenStorage(json_encode(['refresh_token' => 'r'])));

        try {
            $storage->getAccessToken();
            self::fail('Expected TokenException');
        } catch (TokenException $e) {
            self::assertSame('stored token is invalid: Required option not passed: "access_token"', $e->getMessage());
            self::assertInstanceOf(\InvalidArgumentException::class, $e->getPrevious());
        }
    }

    public function testStoreAccessTokenPersistsLeagueJson(): void
    {
        $inner = new FakeTokenStorage();
        $token = new AccessToken([
            'access_token' => 'a',
            'refresh_token' => 'r',
            'expires' => 1_900_000_000,
            'scope' => 'full',
        ]);

        (new InternalTokenStorage($inner))->storeAccessToken($token);

        self::assertSame(json_encode($token), $inner->getToken());
        self::assertSame(
            ['scope' => 'full', 'access_token' => 'a', 'refresh_token' => 'r', 'expires' => 1_900_000_000],
            json_decode($inner->getToken(), true)
        );
    }

    /**
     * @param array<string,mixed> $overrides
     */
    private static function legacyJson(array $overrides): string
    {
        return json_encode($overrides + [
            'provider_id' => 'https://auth.example|client',
            'token_type' => 'Bearer',
            'scope' => 'full',
            'access_token' => 'legacy_access',
            'refresh_token' => 'legacy_refresh',
        ]);
    }
}
