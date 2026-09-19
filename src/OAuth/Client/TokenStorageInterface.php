<?php

declare(strict_types=1);

namespace OnPay\OAuth\Client;

interface TokenStorageInterface
{
    /**
     * @param string $userId
     *
     * @return array<AccessToken>
     */
    public function getAccessTokenList(string $userId): array;

    /**
     * @param string $userId
     *
     * @return void
     */
    public function storeAccessToken(string $userId, AccessToken $accessToken): void;

    /**
     * @param string $userId
     *
     * @return void
     */
    public function deleteAccessToken(string $userId, AccessToken $accessToken): void;
}
