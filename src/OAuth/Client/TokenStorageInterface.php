<?php

namespace OnPay\OAuth\Client;

interface TokenStorageInterface
{
    /**
     * @param string $userId
     *
     * @return array<AccessToken>
     */
    public function getAccessTokenList($userId);

    /**
     * @param string $userId
     *
     * @return void
     */
    public function storeAccessToken($userId, AccessToken $accessToken);

    /**
     * @param string $userId
     *
     * @return void
     */
    public function deleteAccessToken($userId, AccessToken $accessToken);
}
