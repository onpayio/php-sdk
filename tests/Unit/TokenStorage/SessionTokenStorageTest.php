<?php

namespace Tests\Unit\TokenStorage;

use OnPay\OAuth\Client\AccessToken;
use OnPay\OAuth\Client\Exception\SessionException;
use OnPay\OAuth\Client\SessionTokenStorage;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Coverage for OnPay\OAuth\Client\SessionTokenStorage.
 *
 * Runs each test in a separate process: starting a real PHP session requires ini_set() on
 * session.* settings, which PHP refuses once any output has been emitted. Other suites (e.g.
 * CurlHttpClient) emit runtime deprecation notices, so a fresh process per test guarantees a
 * clean, header-free start regardless of suite order.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class SessionTokenStorageTest extends SessionTestCase
{
    private function makeToken(string $providerId, string $token): AccessToken
    {
        return new AccessToken([
            'provider_id' => $providerId,
            'issued_at' => '2020-01-01 00:00:00',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'refresh-' . $token,
            'scope' => 'full',
        ]);
    }

    public function testGetAccessTokenListThrowsWithoutActiveSession(): void
    {
        $storage = new SessionTokenStorage();

        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('no active session');
        $storage->getAccessTokenList('user1');
    }

    public function testStoreAccessTokenThrowsWithoutActiveSession(): void
    {
        $storage = new SessionTokenStorage();

        $this->expectException(SessionException::class);
        $storage->storeAccessToken('user1', $this->makeToken('onpay', 'tok'));
    }

    public function testDeleteAccessTokenThrowsWithoutActiveSession(): void
    {
        $storage = new SessionTokenStorage();

        $this->expectException(SessionException::class);
        $storage->deleteAccessToken('user1', $this->makeToken('onpay', 'tok'));
    }

    public function testGetAccessTokenListReturnsEmptyWhenNothingStored(): void
    {
        $this->startSession();
        $storage = new SessionTokenStorage();

        self::assertSame([], $storage->getAccessTokenList('user1'));
    }

    public function testStoreThenGetReturnsStoredTokens(): void
    {
        $this->startSession();
        $storage = new SessionTokenStorage();

        $t1 = $this->makeToken('onpay', 'tok-1');
        $t2 = $this->makeToken('onpay', 'tok-2');
        $storage->storeAccessToken('user1', $t1);
        $storage->storeAccessToken('user1', $t2);

        $list = $storage->getAccessTokenList('user1');
        self::assertCount(2, $list);
        self::assertSame('tok-1', $list[0]->getToken());
        self::assertSame('tok-2', $list[1]->getToken());
        // tokens are keyed per user; a different user has none
        self::assertSame([], $storage->getAccessTokenList('other'));
    }

    public function testDeleteRemovesOnlyMatchingProviderAndToken(): void
    {
        $this->startSession();
        $storage = new SessionTokenStorage();

        $t1 = $this->makeToken('provider-a', 'tok-1');
        $t2 = $this->makeToken('provider-a', 'tok-2');
        // same token value but different provider — must be left untouched
        $tOtherProvider = $this->makeToken('provider-b', 'tok-1');
        $storage->storeAccessToken('user1', $t1);
        $storage->storeAccessToken('user1', $t2);
        $storage->storeAccessToken('user1', $tOtherProvider);

        $storage->deleteAccessToken('user1', $t1);

        $list = $storage->getAccessTokenList('user1');
        // PINNED CURRENT BEHAVIOUR: delete uses unset() on a list built with `[]=`, so the
        // remaining entries keep their ORIGINAL (non-sequential) numeric keys — the array is
        // not reindexed. Reported to caller as odd-but-current behaviour.
        self::assertSame([1, 2], array_keys($list));
        self::assertSame('tok-2', $list[1]->getToken());
        self::assertSame('provider-b', $list[2]->getProviderId());
        self::assertSame('tok-1', $list[2]->getToken());
    }
}
