<?php

namespace Tests\Unit\OAuth;

use DateTime;
use OnPay\OAuth\Client\AccessToken;
use OnPay\OAuth\Client\Exception\AccessTokenException;
use OnPay\OAuth\Client\Provider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AccessTokenTest extends TestCase
{
    private const PROVIDER_ID = 'https://auth.example.com/authorize|client-123';

    private function validTokenData(array $overrides = []): array
    {
        return \array_merge([
            'provider_id' => self::PROVIDER_ID,
            'issued_at' => '2020-01-01 00:00:00',
            'access_token' => 'the-access-token',
            'token_type' => 'Bearer',
        ], $overrides);
    }

    private function provider(): Provider
    {
        return new Provider('client-123', 'secret', 'https://auth.example.com/authorize', 'https://auth.example.com/token');
    }

    public function testConstructWithRequiredKeysOnly(): void
    {
        $token = new AccessToken($this->validTokenData());

        $this->assertSame(self::PROVIDER_ID, $token->getProviderId());
        $this->assertSame('the-access-token', $token->getToken());
        $this->assertSame('Bearer', $token->getTokenType());
        $this->assertSame('2020-01-01 00:00:00', $token->getIssuedAt()->format('Y-m-d H:i:s'));
        // optional keys default to null
        $this->assertNull($token->getExpiresIn());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getScope());
    }

    public function testConstructWithOptionalKeys(): void
    {
        $token = new AccessToken($this->validTokenData([
            'expires_in' => 3600,
            'refresh_token' => 'the-refresh-token',
            'scope' => 'full read',
        ]));

        $this->assertSame(3600, $token->getExpiresIn());
        $this->assertSame('the-refresh-token', $token->getRefreshToken());
        $this->assertSame('full read', $token->getScope());
    }

    public function testLowercaseBearerTokenTypeAccepted(): void
    {
        $token = new AccessToken($this->validTokenData(['token_type' => 'bearer']));
        $this->assertSame('bearer', $token->getTokenType());
    }

    #[DataProvider('missingRequiredKeyProvider')]
    public function testMissingRequiredKeyThrows(string $missingKey): void
    {
        $data = $this->validTokenData();
        unset($data[$missingKey]);

        $this->expectException(AccessTokenException::class);
        $this->expectExceptionMessage(\sprintf('missing key "%s"', $missingKey));

        new AccessToken($data);
    }

    #[DataProvider('missingRequiredKeyProvider')]
    public function testNonStringRequiredKeyThrows(string $key): void
    {
        $data = $this->validTokenData();
        $data[$key] = ['not', 'a', 'string'];

        $this->expectException(AccessTokenException::class);
        $this->expectExceptionMessage(\sprintf('key "%s" must be a string', $key));

        new AccessToken($data);
    }

    public static function missingRequiredKeyProvider(): array
    {
        return [
            ['provider_id'],
            ['issued_at'],
            ['access_token'],
            ['token_type'],
        ];
    }

    public function testInvalidIssuedAtSyntaxThrows(): void
    {
        $this->expectException(AccessTokenException::class);
        // asserts current behaviour: message names "expires_at" though it validates "issued_at"
        $this->expectExceptionMessage('invalid "expires_at" (syntax)');

        new AccessToken($this->validTokenData(['issued_at' => '01-01-2020 00:00:00']));
    }

    public function testIssuedAtPassesSyntaxButIsInvalidDateThrows(): void
    {
        $this->expectException(AccessTokenException::class);
        // asserts current behaviour: the DateTime catch branch, message also names "expires_at"
        $this->expectExceptionMessage('invalid "expires_at":');

        // matches the regex (####-##-## ##:##:##) but is not a real date/time
        new AccessToken($this->validTokenData(['issued_at' => '2020-13-45 99:99:99']));
    }

    public function testInvalidAccessTokenThrows(): void
    {
        $this->expectException(AccessTokenException::class);
        $this->expectExceptionMessage('invalid "access_token"');

        // contains a byte outside %x20-7E
        new AccessToken($this->validTokenData(['access_token' => "bad\ttoken"]));
    }

    public function testUnsupportedTokenTypeThrows(): void
    {
        $this->expectException(AccessTokenException::class);
        $this->expectExceptionMessage('unsupported "token_type"');

        new AccessToken($this->validTokenData(['token_type' => 'MAC']));
    }

    public function testExpiresInMustBeIntThrows(): void
    {
        $this->expectException(AccessTokenException::class);
        $this->expectExceptionMessage('"expires_in" must be int');

        new AccessToken($this->validTokenData(['expires_in' => '3600']));
    }

    public function testExpiresInZeroOrNegativeThrows(): void
    {
        $this->expectException(AccessTokenException::class);
        $this->expectExceptionMessage('invalid "expires_in"');

        new AccessToken($this->validTokenData(['expires_in' => 0]));
    }

    public function testExpiresInExplicitNullIsAccepted(): void
    {
        // covers the (null !== $expiresIn) false branch
        $token = new AccessToken($this->validTokenData(['expires_in' => null]));
        $this->assertNull($token->getExpiresIn());
    }

    public function testInvalidRefreshTokenThrows(): void
    {
        $this->expectException(AccessTokenException::class);
        $this->expectExceptionMessage('invalid "refresh_token"');

        new AccessToken($this->validTokenData(['refresh_token' => "bad\nrefresh"]));
    }

    public function testRefreshTokenExplicitNullIsAccepted(): void
    {
        // covers the (null !== $refreshToken) false branch
        $token = new AccessToken($this->validTokenData(['refresh_token' => null]));
        $this->assertNull($token->getRefreshToken());
    }

    public function testInvalidScopeThrows(): void
    {
        $this->expectException(AccessTokenException::class);
        $this->expectExceptionMessage('invalid "scope"');

        // space-separated scope-token containing an illegal char
        new AccessToken($this->validTokenData(['scope' => "full bad\"scope"]));
    }

    public function testScopeExplicitNullIsAccepted(): void
    {
        // covers the (null !== $scope) false branch
        $token = new AccessToken($this->validTokenData(['scope' => null]));
        $this->assertNull($token->getScope());
    }

    // --- isExpired: expires_in absent (never expires) ------------------------

    public function testIsExpiredReturnsFalseWhenNoExpiryIndicated(): void
    {
        $token = new AccessToken($this->validTokenData());
        // even a far-future clock is "not expired" when expires_in is absent
        $this->assertFalse($token->isExpired(new DateTime('2999-01-01 00:00:00')));
    }

    // --- isExpired: expires_in present (issued_at + expires_in) --------------

    public function testIsExpiredFalseBeforeExpiry(): void
    {
        $token = new AccessToken($this->validTokenData(['expires_in' => 3600]));
        // issued 00:00:00 + 3600s = 01:00:00; one second before => not expired
        $this->assertFalse($token->isExpired(new DateTime('2020-01-01 00:59:59')));
    }

    public function testIsExpiredTrueExactlyAtExpiryBoundary(): void
    {
        $token = new AccessToken($this->validTokenData(['expires_in' => 3600]));
        // asserts current behaviour: comparison is `>=`, so the exact expiry instant is expired (no leeway/margin)
        $this->assertTrue($token->isExpired(new DateTime('2020-01-01 01:00:00')));
    }

    public function testIsExpiredTrueAfterExpiry(): void
    {
        $token = new AccessToken($this->validTokenData(['expires_in' => 3600]));
        $this->assertTrue($token->isExpired(new DateTime('2020-01-01 01:00:01')));
    }

    // --- JSON round trip -----------------------------------------------------

    public function testToJsonProducesAllFields(): void
    {
        $token = new AccessToken($this->validTokenData([
            'expires_in' => 3600,
            'refresh_token' => 'the-refresh-token',
            'scope' => 'full',
        ]));

        $decoded = \json_decode($token->toJson(), true);

        $this->assertSame([
            'provider_id' => self::PROVIDER_ID,
            'issued_at' => '2020-01-01 00:00:00',
            'access_token' => 'the-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'the-refresh-token',
            'scope' => 'full',
        ], $decoded);
    }

    public function testFromJsonAndBackRoundTrips(): void
    {
        $original = new AccessToken($this->validTokenData([
            'expires_in' => 3600,
            'refresh_token' => 'the-refresh-token',
            'scope' => 'full',
        ]));

        $restored = AccessToken::fromJson($original->toJson());

        $this->assertSame($original->toJson(), $restored->toJson());
    }

    public function testFromJsonThrowsWhenJsonIsNotAnArray(): void
    {
        $this->expectException(AccessTokenException::class);
        $this->expectExceptionMessage('invalid token data');

        AccessToken::fromJson('"a string"');
    }

    // --- fromCodeResponse ----------------------------------------------------

    public function testFromCodeResponseUsesRequestScopeWhenScopeMissing(): void
    {
        $dateTime = new DateTime('2020-06-01 12:00:00');
        $token = AccessToken::fromCodeResponse(
            $this->provider(),
            $dateTime,
            ['access_token' => 'abc', 'token_type' => 'Bearer', 'expires_in' => 60],
            'full'
        );

        $this->assertSame(self::PROVIDER_ID, $token->getProviderId());
        $this->assertSame('full', $token->getScope());
        $this->assertSame('2020-06-01 12:00:00', $token->getIssuedAt()->format('Y-m-d H:i:s'));
        $this->assertSame('abc', $token->getToken());
    }

    public function testFromCodeResponseKeepsResponseScopeWhenPresent(): void
    {
        $token = AccessToken::fromCodeResponse(
            $this->provider(),
            new DateTime('2020-06-01 12:00:00'),
            ['access_token' => 'abc', 'token_type' => 'Bearer', 'scope' => 'read'],
            'full'
        );

        $this->assertSame('read', $token->getScope());
    }

    // --- fromRefreshResponse -------------------------------------------------

    public function testFromRefreshResponseBorrowsScopeAndRefreshTokenWhenMissing(): void
    {
        $old = new AccessToken($this->validTokenData([
            'expires_in' => 3600,
            'refresh_token' => 'old-refresh',
            'scope' => 'full',
        ]));

        $token = AccessToken::fromRefreshResponse(
            $this->provider(),
            new DateTime('2020-06-01 12:00:00'),
            ['access_token' => 'new-access', 'token_type' => 'Bearer'],
            $old
        );

        $this->assertSame('new-access', $token->getToken());
        $this->assertSame('full', $token->getScope(), 'scope borrowed from old token');
        $this->assertSame('old-refresh', $token->getRefreshToken(), 'refresh_token borrowed from old token');
        $this->assertSame(self::PROVIDER_ID, $token->getProviderId());
        $this->assertSame('2020-06-01 12:00:00', $token->getIssuedAt()->format('Y-m-d H:i:s'));
    }

    public function testFromRefreshResponseKeepsResponseScopeAndRefreshTokenWhenPresent(): void
    {
        $old = new AccessToken($this->validTokenData([
            'refresh_token' => 'old-refresh',
            'scope' => 'full',
        ]));

        $token = AccessToken::fromRefreshResponse(
            $this->provider(),
            new DateTime('2020-06-01 12:00:00'),
            [
                'access_token' => 'new-access',
                'token_type' => 'Bearer',
                'refresh_token' => 'new-refresh',
                'scope' => 'read',
            ],
            $old
        );

        $this->assertSame('new-refresh', $token->getRefreshToken());
        $this->assertSame('read', $token->getScope());
    }
}
