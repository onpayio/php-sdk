<?php

declare(strict_types=1);

namespace OnPay;

use OnPay\API\Exception\TokenException;

/**
 * This object is meant for use with static API tokens from OnPay.
 * In order to construct this object, a static API token created in in OnPay management panel is needed.
 *
 * The implementation is fairly simple and is used with OnPayAPI like this:
 *
 *      $tokenStorage = new StaticToken({STATIC_API_TOKEN});
 *      $onPayAPI = new OnPayAPI($tokenStorage, []);
 *
 *
 * Class StaticToken
 * @package OnPay
 */

final class StaticToken implements TokenStorageInterface {
    protected string $staticToken;

    /**
     * StaticToken constructor.
     * @param string $staticToken
     */
    public function __construct(string $staticToken) {
        $this->staticToken = $staticToken;
    }

    /**
     * Static API tokens neither expire nor refresh, so no expiry is set.
     *
     * @return string|null
     */
    public function getToken(): ?string {
        try {
            return json_encode([
                'access_token' => $this->staticToken,
                'token_type' => 'Bearer',
                'scope' => 'full',
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException $e) {
            throw new TokenException('Failed to encode static token: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Dummy method, we do not need to save anything in this tokenstorage
     *
     * @param string $token
     * @return void
     */
    public function saveToken(string $token): void {}
}
