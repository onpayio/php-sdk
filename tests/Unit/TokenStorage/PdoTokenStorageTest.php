<?php

namespace Tests\Unit\TokenStorage;

use OnPay\OAuth\Client\AccessToken;
use OnPay\OAuth\Client\PdoTokenStorage;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for OnPay\OAuth\Client\PdoTokenStorage.
 *
 * Uses an in-memory SQLite database for the full lifecycle, plus a PDO mock to reach the
 * non-sqlite constructor branch (which must skip the PRAGMA query).
 */
class PdoTokenStorageTest extends TestCase
{
    private function newAccessToken(?int $expiresIn, ?string $refreshToken): AccessToken
    {
        return new AccessToken([
            'provider_id' => 'onpay',
            'issued_at' => '2020-01-01 00:00:00',
            'access_token' => 'tok-abc',
            'token_type' => 'bearer',
            'expires_in' => $expiresIn,
            'refresh_token' => $refreshToken,
            'scope' => 'full',
        ]);
    }

    public function testFullLifecycleWithSqlite(): void
    {
        $db = new PDO('sqlite::memory:');
        $storage = new PdoTokenStorage($db);
        $storage->init();

        // empty list first
        self::assertSame([], $storage->getAccessTokenList('user1'));

        // store one WITH expires_in / refresh_token set
        $t1 = $this->newAccessToken(3600, 'refresh-1');
        $storage->storeAccessToken('user1', $t1);

        // store one with expires_in / refresh_token NULL to hit the null-cast branch
        $t2 = new AccessToken([
            'provider_id' => 'onpay2',
            'issued_at' => '2021-06-06 12:34:56',
            'access_token' => 'tok-def',
            'token_type' => 'Bearer',
            'expires_in' => null,
            'refresh_token' => null,
            'scope' => 'read',
        ]);
        $storage->storeAccessToken('user1', $t2);

        $list = $storage->getAccessTokenList('user1');
        self::assertCount(2, $list);

        $withExpiry = null;
        $withoutExpiry = null;
        foreach ($list as $tok) {
            if ('onpay' === $tok->getProviderId()) {
                $withExpiry = $tok;
            } else {
                $withoutExpiry = $tok;
            }
        }
        self::assertNotNull($withExpiry);
        self::assertNotNull($withoutExpiry);
        self::assertSame(3600, $withExpiry->getExpiresIn());
        self::assertSame('tok-abc', $withExpiry->getToken());
        self::assertNull($withoutExpiry->getExpiresIn());
        self::assertNull($withoutExpiry->getRefreshToken());
        self::assertSame('tok-def', $withoutExpiry->getToken());

        // delete the first one; only it disappears
        $storage->deleteAccessToken('user1', $t1);
        $list = $storage->getAccessTokenList('user1');
        self::assertCount(1, $list);
        self::assertSame('onpay2', $list[0]->getProviderId());
    }

    public function testConstructorNonSqliteDriverSkipsPragma(): void
    {
        // Non-sqlite driver: the constructor must NOT run "PRAGMA foreign_keys = ON".
        $db = $this->createMock(PDO::class);
        $db->method('getAttribute')->willReturn('mysql');
        $db->expects(self::never())->method('query');

        $storage = new PdoTokenStorage($db);
        self::assertInstanceOf(PdoTokenStorage::class, $storage);
    }
}
