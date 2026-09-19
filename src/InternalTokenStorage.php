<?php

declare(strict_types=1);

namespace OnPay;

use OnPay\OAuth\Client\AccessToken;
use OnPay\OAuth\Client\Exception\AccessTokenException;
use OnPay\OAuth\Client\TokenStorageInterface as oauthTokenStorageInterface;
use OnPay\TokenStorageInterface as onpayTokenStorageInterface;

class InternalTokenStorage implements oauthTokenStorageInterface {
    /**
     * @var onpayTokenStorageInterface
     */
    protected onpayTokenStorageInterface $onpayTokenInterface;

    /**
     * @var string
     */
    protected string $authUrl;

    /**
     * @var string
     */
    protected string $clientId;

    /**
     * @var string
     */
    protected string $scope;

    /**
     * InternalTokenStorage constructor.
     * @param onpayTokenStorageInterface $storageToken
     * @param string $authUrl
     * @param string $clientId
     * @param string $scope
     */
    public function __construct(onpayTokenStorageInterface $storageToken, string $authUrl, string $clientId, string $scope) {
        $this->onpayTokenInterface = $storageToken;
        $this->authUrl = $authUrl;
        $this->clientId = $clientId;
        $this->scope = $scope;
    }

    /**
     * @param string $userId
     * @return array<AccessToken>
     * @throws \OnPay\OAuth\Client\Exception\AccessTokenException
     */
    public function getAccessTokenList(string $userId): array {
        $accessToken = $this->getToken();
        if(null !== $accessToken) {
            return [
                $accessToken,
            ];
        }
        return [];
    }

    /**
     * @param string $userId
     * @param AccessToken $accessToken
     */
    public function storeAccessToken(string $userId, AccessToken $accessToken): void {
        $this->onpayTokenInterface->saveToken($accessToken->toJson());
    }

    /**
     * @param string $userId
     * @param AccessToken $accessToken
     */
    public function deleteAccessToken(string $userId, AccessToken $accessToken): void {}

    /**
     * @return AccessToken|null
     * @throws \OnPay\OAuth\Client\Exception\AccessTokenException
     */
    private function getToken(): ?AccessToken {
        if ($this->onpayTokenInterface instanceof StaticToken) {
            // When a static token is used, we need to supply it with the Authorize URL and Client ID.
            $json = $this->onpayTokenInterface->getToken($this->clientId, $this->authUrl);
        } else {
            $json = $this->onpayTokenInterface->getToken();
        }
        if(null !== $json && '' !== $json) {
            if (strpos($json, 'provider_id') !== false) {
                // Json is of OnPay/oauth2-client format
                $accessToken = AccessToken::fromJson($json);
            } else {
                // Json is of league/oauth2-client format
                $this->convertToken();
                $accessToken = AccessToken::fromJson((string) $this->onpayTokenInterface->getToken());
            }
            return $accessToken;
        }
        return null;
    }

    /**
     * Convert the token from the old league/oauth2-client format to OnPay/oauth2-client format
     */
    private function convertToken(): void {
        try {
            /** @var array<array-key, mixed> $decoded */
            $decoded = (array) json_decode((string) $this->onpayTokenInterface->getToken(), true, 512, JSON_THROW_ON_ERROR);

            // Populate required fields with data indicating that the access token is expired, triggering the oauth2 client to refresh it.
            $decoded['provider_id'] = $this->authUrl . '|' . $this->clientId;
            $decoded['issued_at'] = date('Y-m-d H:i:s', (int) strtotime('-1 month'));
            $decoded['expires_in'] = 3600;
            $decoded['scope'] = $this->scope;

            $json = json_encode($decoded, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException $e) {
            throw new AccessTokenException('Failed to convert stored token: ' . $e->getMessage(), $e->getCode(), $e);
        }

        $this->onpayTokenInterface->saveToken($json);
    }
}
