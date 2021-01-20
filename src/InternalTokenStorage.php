<?php

namespace OnPay;


use fkooman\OAuth\Client\AccessToken;
use fkooman\OAuth\Client\TokenStorageInterface as oauthTokenStorageInterface;
use OnPay\TokenStorageInterface as onpayTokenStorageInterface;

class InternalTokenStorage implements oauthTokenStorageInterface {
    /**
     * @var onpayTokenStorageInterface
     */
    protected $onpayTokenInterface;

    /**
     * @var string
     */
    protected $authUrl;

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $scope;

    /**
     * InternalTokenStorage constructor.
     * @param TokenStorageInterface $storageToken
     * @param $authUrl
     * @param $clientId
     * @param $scope
     */
    public function __construct($storageToken, $authUrl, $clientId, $scope) {
        $this->onpayTokenInterface = $storageToken;
        $this->authUrl = $authUrl;
        $this->clientId = $clientId;
        $this->scope = $scope;
    }

    /**
     * @param string $userId
     * @return array
     * @throws \fkooman\OAuth\Client\Exception\AccessTokenException
     */
    public function getAccessTokenList($userId) {
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
    public function storeAccessToken($userId, AccessToken $accessToken) {
        $this->onpayTokenInterface->saveToken($accessToken->toJson());
    }

    /**
     * @param string $userId
     * @param AccessToken $accessToken
     */
    public function deleteAccessToken($userId, AccessToken $accessToken) {}

    /**
     * @return AccessToken|null
     * @throws \fkooman\OAuth\Client\Exception\AccessTokenException
     */
    private function getToken() {
        if ($this->onpayTokenInterface instanceof StaticToken) {
            // When a static token is used, we need to supply it with the Authorize URL and Client ID.
            $json = $this->onpayTokenInterface->getToken($this->clientId, $this->authUrl);
        } else {
            $json = $this->onpayTokenInterface->getToken();
        }
        if(null !== $json && '' !== $json) {
            if (strpos($json, 'provider_id') !== false) {
                // Json is of fkooman/oauth2-client format
                $accessToken = AccessToken::fromJson($json);
            } else {
                // Json is of league/oauth2-client format
                $this->convertToken();
                $accessToken = AccessToken::fromJson($this->onpayTokenInterface->getToken());
            }
            return $accessToken;
        }
        return null;
    }

    /**
     * Convert the token from the old league/oauth2-client format to fkooman/oauth2-client format
     */
    private function convertToken() {
        $json = $this->onpayTokenInterface->getToken();
        $decoded = json_decode($json, true);

        // Populate required fields with data indicating that the access token is expired, triggering the oauth2 client to refresh it.
        $decoded['provider_id'] = $this->authUrl . '|' . $this->clientId;
        $decoded['issued_at'] = date('Y-m-d H:i:s', strtotime('-1 month'));
        $decoded['expires_in'] = 3600;
        $decoded['scope'] = $this->scope;

        $json = json_encode($decoded);

        $this->onpayTokenInterface->saveToken($json);
    }
}
