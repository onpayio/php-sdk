<?php

namespace OnPay\OAuth\Client;

use OnPay\OAuth\Client\Exception\SessionException;

class SessionTokenStorage implements TokenStorageInterface
{
    /**
     * @param string $userId
     *
     * @return array<AccessToken>
     */
    public function getAccessTokenList($userId)
    {
        self::requireSession();
        if (false === \array_key_exists(\sprintf('_oauth2_token_%s', $userId), $_SESSION)) {
            return [];
        }

        return $_SESSION[\sprintf('_oauth2_token_%s', $userId)];
    }

    /**
     * @param string $userId
     *
     * @return void
     */
    public function storeAccessToken($userId, AccessToken $accessToken)
    {
        self::requireSession();
        $_SESSION[\sprintf('_oauth2_token_%s', $userId)][] = $accessToken;
    }

    /**
     * @param string $userId
     *
     * @return void
     */
    public function deleteAccessToken($userId, AccessToken $accessToken)
    {
        self::requireSession();
        foreach ($this->getAccessTokenList($userId) as $k => $v) {
            if ($accessToken->getProviderId() === $v->getProviderId()) {
                if ($accessToken->getToken() === $v->getToken()) {
                    unset($_SESSION[\sprintf('_oauth2_token_%s', $userId)][$k]);
                }
            }
        }
    }

    /**
     * @return void
     */
    private static function requireSession()
    {
        if (PHP_SESSION_ACTIVE !== \session_status()) {
            // if we have no active session, bail, we expect an active session
            // and will NOT fiddle with the application's existing session
            // management
            throw new SessionException('no active session');
        }
    }
}
