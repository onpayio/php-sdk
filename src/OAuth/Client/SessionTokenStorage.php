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
        $key = \sprintf('_oauth2_token_%s', $userId);
        if (false === isset($_SESSION[$key])) {
            return [];
        }

        return \array_filter(
            \is_array($_SESSION[$key]) ? $_SESSION[$key] : [],
            static function ($token): bool {
                return $token instanceof AccessToken;
            }
        );
    }

    /**
     * @param string $userId
     *
     * @return void
     */
    public function storeAccessToken($userId, AccessToken $accessToken)
    {
        self::requireSession();
        $key = \sprintf('_oauth2_token_%s', $userId);
        $list = isset($_SESSION[$key]) && \is_array($_SESSION[$key]) ? $_SESSION[$key] : [];
        $list[] = $accessToken;
        $_SESSION[$key] = $list;
    }

    /**
     * @param string $userId
     *
     * @return void
     */
    public function deleteAccessToken($userId, AccessToken $accessToken)
    {
        self::requireSession();
        $key = \sprintf('_oauth2_token_%s', $userId);
        if (false === isset($_SESSION[$key])) {
            return;
        }
        $list = $this->getAccessTokenList($userId);
        foreach ($list as $k => $v) {
            if ($accessToken->getProviderId() === $v->getProviderId()) {
                if ($accessToken->getToken() === $v->getToken()) {
                    unset($list[$k]);
                }
            }
        }
        $_SESSION[$key] = $list;
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
