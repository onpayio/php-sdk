<?php

namespace Tests\Unit\TokenStorage;

use OnPay\InternalTokenStorage;
use OnPay\OAuth\Client\AccessToken;
use OnPay\OAuth\Client\Exception\AccessTokenException;
use OnPay\StaticToken;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for OnPay\InternalTokenStorage.
 */
class InternalTokenStorageTest extends TestCase
{
    private const AUTH_URL = 'https://auth.example';
    private const CLIENT_ID = 'client-123';
    private const SCOPE = 'full';

    private function makeStorage($inner): InternalTokenStorage
    {
        return new InternalTokenStorage($inner, self::AUTH_URL, self::CLIENT_ID, self::SCOPE);
    }

    private function onPayFormatJson(): string
    {
        return json_encode([
            'provider_id' => self::AUTH_URL . '|' . self::CLIENT_ID,
            'issued_at' => date('Y-m-d H:i:s'),
            'access_token' => 'tok-onpay',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'refresh-onpay',
            'scope' => self::SCOPE,
        ]);
    }

    public function testGetAccessTokenListReturnsEmptyWhenNoToken(): void
    {
        $storage = $this->makeStorage(new FakeTokenStorage(null));

        self::assertSame([], $storage->getAccessTokenList('user1'));
    }

    public function testGetAccessTokenListReturnsTokenForOnPayFormat(): void
    {
        $storage = $this->makeStorage(new FakeTokenStorage($this->onPayFormatJson()));

        $list = $storage->getAccessTokenList('user1');
        self::assertCount(1, $list);
        self::assertInstanceOf(AccessToken::class, $list[0]);
        self::assertSame('tok-onpay', $list[0]->getToken());
        self::assertSame(self::AUTH_URL . '|' . self::CLIENT_ID, $list[0]->getProviderId());
    }

    public function testGetAccessTokenListUsesStaticTokenBranch(): void
    {
        // A StaticToken triggers the branch that passes clientId + authUrl into getToken().
        $storage = $this->makeStorage(new StaticToken('my-static-token'));

        $list = $storage->getAccessTokenList('user1');
        self::assertCount(1, $list);
        self::assertSame('my-static-token', $list[0]->getToken());
        // StaticToken builds provider_id as "{authorize_uri}|{client_id}"
        self::assertSame(self::AUTH_URL . '|' . self::CLIENT_ID, $list[0]->getProviderId());
    }

    public function testGetAccessTokenListConvertsLeagueFormatToken(): void
    {
        // League format: valid token JSON WITHOUT the "provider_id" substring, so the
        // conversion branch runs (convertToken() then re-reads the saved token).
        $leagueJson = json_encode([
            'access_token' => 'tok-league',
            'token_type' => 'Bearer',
            'refresh_token' => 'refresh-league',
        ]);
        $inner = new FakeTokenStorage($leagueJson);
        $storage = $this->makeStorage($inner);

        $list = $storage->getAccessTokenList('user1');
        self::assertCount(1, $list);
        $token = $list[0];
        self::assertSame('tok-league', $token->getToken());
        // conversion stamps provider_id from authUrl + clientId...
        self::assertSame(self::AUTH_URL . '|' . self::CLIENT_ID, $token->getProviderId());
        // ...and deliberately back-dates issued_at so the token reads as expired, forcing a refresh
        self::assertTrue($token->isExpired(new \DateTime()));

        // the conversion was persisted through saveToken(): the stored JSON now carries provider_id
        $persisted = json_decode($inner->getToken(), true);
        self::assertSame(self::AUTH_URL . '|' . self::CLIENT_ID, $persisted['provider_id']);
        self::assertSame(self::SCOPE, $persisted['scope']);
    }

    public function testConvertTokenThrowsOnMalformedStoredToken(): void
    {
        // A non-empty token without the "provider_id" substring routes to convertToken(),
        // whose JSON_THROW_ON_ERROR decode now surfaces a typed AccessTokenException
        // instead of silently tolerating the corrupt token as an empty array.
        $storage = $this->makeStorage(new FakeTokenStorage('{not valid json'));

        $this->expectException(AccessTokenException::class);
        $this->expectExceptionMessage('Failed to convert stored token');
        $storage->getAccessTokenList('user1');
    }

    public function testStoreAccessTokenPersistsJson(): void
    {
        $inner = new FakeTokenStorage(null);
        $storage = $this->makeStorage($inner);

        $accessToken = new AccessToken([
            'provider_id' => 'onpay',
            'issued_at' => '2020-01-01 00:00:00',
            'access_token' => 'tok-store',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'refresh-store',
            'scope' => self::SCOPE,
        ]);

        $storage->storeAccessToken('user1', $accessToken);

        $persisted = json_decode($inner->getToken(), true);
        self::assertSame('tok-store', $persisted['access_token']);
        self::assertSame('onpay', $persisted['provider_id']);
    }

    public function testDeleteAccessTokenIsANoop(): void
    {
        $inner = new FakeTokenStorage($this->onPayFormatJson());
        $storage = $this->makeStorage($inner);

        $accessToken = $storage->getAccessTokenList('user1')[0];

        // deleteAccessToken is intentionally empty: it must not throw and must not change state.
        $storage->deleteAccessToken('user1', $accessToken);

        self::assertCount(1, $storage->getAccessTokenList('user1'));
    }
}
