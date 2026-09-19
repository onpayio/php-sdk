<?php

namespace Tests\Unit\TokenStorage;

use OnPay\API\Exception\TokenException;
use OnPay\StaticToken;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for OnPay\StaticToken.
 */
class StaticTokenTest extends TestCase
{
    public function testGetTokenBuildsJsonFromArguments(): void
    {
        $storage = new StaticToken('my-static-token');

        $json = $storage->getToken('client-123', 'https://auth.example');
        $decoded = json_decode($json, true);

        self::assertSame('https://auth.example|client-123', $decoded['provider_id']);
        self::assertSame('my-static-token', $decoded['access_token']);
        self::assertSame('Bearer', $decoded['token_type']);
        self::assertSame(3600, $decoded['expires_in']);
        self::assertSame('full', $decoded['scope']);
        self::assertMatchesRegularExpression(
            '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/',
            $decoded['issued_at']
        );
    }

    public function testGetTokenWithDefaultNullArguments(): void
    {
        $storage = new StaticToken('tok');

        $decoded = json_decode($storage->getToken(), true);

        // both client_id and authorize_uri default to null, so provider_id is just the separator
        self::assertSame('|', $decoded['provider_id']);
        self::assertSame('tok', $decoded['access_token']);
    }

    public function testGetTokenThrowsWhenTokenCannotBeJsonEncoded(): void
    {
        // Invalid UTF-8 cannot be JSON-encoded: getToken() now surfaces a typed
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
