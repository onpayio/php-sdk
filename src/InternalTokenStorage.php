<?php

declare(strict_types=1);

namespace OnPay;

use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use OnPay\API\Exception\TokenException;
use OnPay\API\Util\DataReader;

/**
 * Bridges the consumer's TokenStorageInterface and league/oauth2-client's AccessToken.
 *
 * Tokens are persisted as league's JSON (`access_token`, `refresh_token`, absolute
 * `expires` timestamp, ...). Tokens written by 1.x (`issued_at` + `expires_in`,
 * `provider_id`) are converted on read, keeping their refresh token and their real
 * expiry, and re-saved in the new format, so existing integrations keep working
 * without re-authorizing.
 *
 * @internal Shall not be used outside the library.
 */
final class InternalTokenStorage {
    private TokenStorageInterface $storage;

    public function __construct(TokenStorageInterface $storage) {
        $this->storage = $storage;
    }

    /**
     * @throws TokenException when the stored token cannot be parsed
     */
    public function getAccessToken(): ?AccessToken {
        $json = $this->storage->getToken();
        if (null === $json || '' === $json) {
            return null;
        }

        $data = \json_decode($json, true);
        if (!\is_array($data)) {
            throw new TokenException('stored token is not valid JSON');
        }

        $legacy = null !== DataReader::stringOrNull($data, 'issued_at');
        if ($legacy) {
            $data = self::convertLegacyToken($data);
        }

        try {
            $token = new AccessToken($data);
        } catch (\InvalidArgumentException $e) {
            throw new TokenException('stored token is invalid: ' . $e->getMessage(), 0, $e);
        }

        if ($legacy) {
            $this->storeAccessToken($token);
        }

        return $token;
    }

    public function storeAccessToken(AccessTokenInterface $token): void {
        $this->storage->saveToken(\json_encode($token, JSON_THROW_ON_ERROR));
    }

    /**
     * Maps the 1.x token layout (`issued_at` + relative `expires_in`) onto league's
     * absolute `expires`, dropping the fields only the old client understood. A token
     * without `expires_in` never expires, as in 1.x.
     *
     * @param array<array-key,mixed> $data
     *
     * @return array<array-key,mixed>
     *
     * @throws TokenException
     */
    private static function convertLegacyToken(array $data): array {
        $issuedAt = \strtotime(DataReader::requireString($data, 'issued_at'));
        if (false === $issuedAt) {
            throw new TokenException('stored token has an invalid "issued_at"');
        }

        $expiresIn = DataReader::intOrNull($data, 'expires_in');
        unset($data['provider_id'], $data['issued_at'], $data['expires_in']);

        if (null !== $expiresIn) {
            $data['expires'] = $issuedAt + $expiresIn;
        }

        return $data;
    }
}
