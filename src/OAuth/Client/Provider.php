<?php

declare(strict_types=1);

namespace OnPay\OAuth\Client;

class Provider {
    /** @var string */
    private string $clientId;

    /** @var string */
    private string $clientSecret;

    /** @var string */
    private string $authorizationEndpoint;

    /** @var string */
    private string $tokenEndpoint;

    public function __construct(string $clientId, string $clientSecret, string $authorizationEndpoint, string $tokenEndpoint)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->authorizationEndpoint = $authorizationEndpoint;
        $this->tokenEndpoint = $tokenEndpoint;
    }

    public function getProviderId(): string
    {
        return \sprintf('%s|%s', $this->getAuthorizationEndpoint(), $this->getClientId());
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getSecret(): string
    {
        return $this->clientSecret;
    }

    public function getAuthorizationEndpoint(): string
    {
        return $this->authorizationEndpoint;
    }

    public function getTokenEndpoint(): string
    {
        return $this->tokenEndpoint;
    }
}
