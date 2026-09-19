<?php

namespace OnPay;

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

class StaticToken implements TokenStorageInterface {
    protected $staticToken;

    /**
     * StaticToken constructor.
     * @param string $staticToken
     */
    public function __construct(string $staticToken) {
        $this->staticToken = $staticToken;
    }

    /**
     * @param string|null $client_id
     * @param string|null $authorize_uri
     * @return string|null
     */
    public function getToken(string $client_id = null, string $authorize_uri = null) {
        $json = json_encode([
            'provider_id' => (string) $authorize_uri . '|' . (string) $client_id,
            'issued_at' => date('Y-m-d H:i:s'),
            'access_token' => $this->staticToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'full',
        ]);

        return false === $json ? null : $json;
    }

    /**
     * Dummy method, we do not need to save anything in this tokenstorage
     *
     * @param string $token
     * @return void
     */
    public function saveToken($token) {}
}
