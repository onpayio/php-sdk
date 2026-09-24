<?php

namespace Tests\Unit\OAuth;

use OnPay\OAuth\OnPayOptionProvider;
use PHPUnit\Framework\TestCase;

class OnPayOptionProviderTest extends TestCase
{
    public function testAuthorizationCodeGrantIsFormEncodedWithBasicClientIdAndNoVerifierWhenNoneSupplied(): void
    {
        $options = (new OnPayOptionProvider())->getAccessTokenOptions('POST', [
            'client_id' => 'cid',
            'client_secret' => 'must-not-be-sent',
            'grant_type' => 'authorization_code',
            'code' => 'the-code',
        ]);

        self::assertSame([
            'content-type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode('cid:'),
        ], $options['headers']);
        // Without PKCE no verifier is sent at all (the client_secret is dropped).
        self::assertSame('client_id=cid&grant_type=authorization_code&code=the-code', $options['body']);
    }

    public function testAuthorizationCodeGrantKeepsAKnownVerifier(): void
    {
        $options = (new OnPayOptionProvider())->getAccessTokenOptions('POST', [
            'client_id' => 'cid',
            'grant_type' => 'authorization_code',
            'code' => 'the-code',
            'code_verifier' => 'verifier',
        ]);

        self::assertSame('client_id=cid&grant_type=authorization_code&code=the-code&code_verifier=verifier', $options['body']);
    }

    public function testRefreshTokenGrantCarriesNoVerifier(): void
    {
        $options = (new OnPayOptionProvider())->getAccessTokenOptions('POST', [
            'client_id' => 'cid',
            'grant_type' => 'refresh_token',
            'refresh_token' => 'r',
        ]);

        self::assertSame('client_id=cid&grant_type=refresh_token&refresh_token=r', $options['body']);
    }

    public function testClientIdIsRequired(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('client_id is required for the OnPay token endpoint');
        (new OnPayOptionProvider())->getAccessTokenOptions('POST', ['grant_type' => 'refresh_token']);
    }
}
