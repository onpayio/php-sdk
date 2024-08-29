<?php

namespace OnPay\OAuth\Client;

class Provider {
    /** @var string */
    private $clientId;

    /** @var string */
    private $clientSecret;

    /** @var string */
    private $authorizationEndpoint;

    /** @var string */
    private $tokenEndpoint;

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $authorizationEndpoint
     * @param string $tokenEndpoint
     */
    public function __construct($clientId, $clientSecret, $authorizationEndpoint, $tokenEndpoint)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->authorizationEndpoint = $authorizationEndpoint;
        $this->tokenEndpoint = $tokenEndpoint;
    }

    /**
     * @return string
     */
    public function getProviderId()
    {
        return \sprintf('%s|%s', $this->getAuthorizationEndpoint(), $this->getClientId());
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @return string
     */
    public function getAuthorizationEndpoint()
    {
        return $this->authorizationEndpoint;
    }

    /**
     * @return string
     */
    public function getTokenEndpoint()
    {
        return $this->tokenEndpoint;
    }
}
