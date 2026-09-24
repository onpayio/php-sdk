<?php

namespace Tests\Unit\TokenStorage;

use League\OAuth2\Client\Token\AccessToken;
use OnPay\API\Exception\TokenException;
use OnPay\StaticToken;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for OnPay\StaticToken.
 */
class StaticTokenTest extends TestCase
{
    public function testGetTokenBuildsNonExpiringLeagueToken(): void
    {
        $storage = new StaticToken('my-static-token');

        $json = $storage->getToken();
        $decoded = json_decode($json, true);

        self::assertSame([
            'access_token' => 'my-static-token',
            'token_type' => 'Bearer',
            'scope' => 'full',
        ], $decoded);

        // Round-trips into league's AccessToken without an expiry or refresh token.
        $token = new AccessToken($decoded);
        self::assertSame('my-static-token', $token->getToken());
        self::assertNull($token->getExpires());
        self::assertNull($token->getRefreshToken());
    }

    public function testGetTokenThrowsWhenTokenCannotBeJsonEncoded(): void
    {
        // Invalid UTF-8 cannot be JSON-encoded: getToken() surfaces a typed
        // TokenException instead of silently returning null.
        $storage = new StaticToken("\xB1\x31");

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('Failed to encode static token');
        $storage->getToken();
    }

    public function testSaveTokenIsANoop(): void
    {
        $storage = new StaticToken('tok');

        // saveToken is a documented dummy; it returns nothing and must not error
        self::assertNull($storage->saveToken('anything'));
    }
}
