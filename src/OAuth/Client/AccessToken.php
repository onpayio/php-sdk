<?php

namespace OnPay\OAuth\Client;

use DateInterval;
use DateTime;
use Exception;
use OnPay\OAuth\Client\Exception\AccessTokenException;

class AccessToken
{
    /** @var string */
    private $providerId;

    /** @var \DateTime */
    private $issuedAt;

    /** @var string */
    private $accessToken;

    /** @var string */
    private $tokenType;

    /** @var int|null */
    private $expiresIn = null;

    /** @var string|null */
    private $refreshToken = null;

    /** @var string|null */
    private $scope = null;

    public function __construct(array $tokenData)
    {
        $requiredKeys = ['provider_id', 'issued_at', 'access_token', 'token_type'];
        foreach ($requiredKeys as $requiredKey) {
            if (false === \array_key_exists($requiredKey, $tokenData)) {
                throw new AccessTokenException(\sprintf('missing key "%s"', $requiredKey));
            }
        }

        // set required keys
        $this->setProviderId($tokenData['provider_id']);
        $this->setIssuedAt($tokenData['issued_at']);
        $this->setAccessToken($tokenData['access_token']);
        $this->setTokenType($tokenData['token_type']);

        // set optional keys
        if (\array_key_exists('expires_in', $tokenData)) {
            $this->setExpiresIn($tokenData['expires_in']);
        }
        if (\array_key_exists('refresh_token', $tokenData)) {
            $this->setRefreshToken($tokenData['refresh_token']);
        }
        if (\array_key_exists('scope', $tokenData)) {
            $this->setScope($tokenData['scope']);
        }
    }

    /**
     * @param string $scope
     *
     * @return AccessToken
     */
    public static function fromCodeResponse(Provider $provider, DateTime $dateTime, array $tokenData, $scope)
    {
        $tokenData['provider_id'] = $provider->getProviderId();

        // if the scope was not part of the response, add the request scope,
        // because according to the RFC, if the scope is ommitted the requested
        // scope was granted!
        if (false === \array_key_exists('scope', $tokenData)) {
            $tokenData['scope'] = $scope;
        }
        // add the current DateTime as well to be able to figure out if the
        // token expired
        $tokenData['issued_at'] = $dateTime->format('Y-m-d H:i:s');

        return new self($tokenData);
    }

    /**
     * @param AccessToken $accessToken to steal the old scope and refresh_token from!
     *
     * @return AccessToken
     */
    public static function fromRefreshResponse(Provider $provider, DateTime $dateTime, array $tokenData, self $accessToken)
    {
        $tokenData['provider_id'] = $provider->getProviderId();

        // if the scope is not part of the response, add the request scope,
        // because according to the RFC, if the scope is ommitted the requested
        // scope was granted!
        if (false === \array_key_exists('scope', $tokenData)) {
            $tokenData['scope'] = $accessToken->getScope();
        }
        // if the refresh_token is not part of the response, we wil reuse the
        // existing refresh_token for future refresh_token requests
        if (false === \array_key_exists('refresh_token', $tokenData)) {
            $tokenData['refresh_token'] = $accessToken->getRefreshToken();
        }
        // add the current DateTime as well to be able to figure out if the
        // token expired
        $tokenData['issued_at'] = $dateTime->format('Y-m-d H:i:s');

        return new self($tokenData);
    }

    /**
     * @return string
     */
    public function getProviderId()
    {
        return $this->providerId;
    }

    /**
     * @return \DateTime
     */
    public function getIssuedAt()
    {
        return $this->issuedAt;
    }

    /**
     * @return string
     *
     * @see https://tools.ietf.org/html/rfc6749#section-5.1
     */
    public function getToken()
    {
        return $this->accessToken;
    }

    /**
     * @return string
     *
     * @see https://tools.ietf.org/html/rfc6749#section-7.1
     */
    public function getTokenType()
    {
        return $this->tokenType;
    }

    /**
     * @return int|null
     *
     * @see https://tools.ietf.org/html/rfc6749#section-5.1
     */
    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    /**
     * @return string|null the refresh token
     *
     * @see https://tools.ietf.org/html/rfc6749#section-1.5
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @return string|null
     *
     * @see https://tools.ietf.org/html/rfc6749#section-3.3
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return bool
     */
    public function isExpired(DateTime $dateTime)
    {
        if (null === $expiresIn = $this->getExpiresIn()) {
            // if no expiry was indicated, assume it is valid
            return false;
        }

        // check to see if issuedAt + expiresIn > provided DateTime
        $expiresAt = clone $this->issuedAt;
        $expiresAt->add(new DateInterval(\sprintf('PT%dS', $expiresIn)));

        return $dateTime >= $expiresAt;
    }

    /**
     * @param string $jsonString
     *
     * @return self
     */
    public static function fromJson($jsonString)
    {
        return new self(Json::decode($jsonString));
    }

    /**
     * @return string
     */
    public function toJson()
    {
        $jsonData = [
                'provider_id' => $this->getProviderId(),
                'issued_at' => $this->issuedAt->format('Y-m-d H:i:s'),
                'access_token' => $this->getToken(),
                'token_type' => $this->getTokenType(),
                'expires_in' => $this->getExpiresIn(),
                'refresh_token' => $this->getRefreshToken(),
                'scope' => $this->getScope(),
        ];

        return Json::encode($jsonData);
    }

    /**
     * @param string $providerId
     *
     * @return void
     */
    private function setProviderId($providerId)
    {
        $this->providerId = $providerId;
    }

    /**
     * @param string $issuedAt
     *
     * @return void
     */
    private function setIssuedAt($issuedAt)
    {
        if (1 !== \preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/', $issuedAt)) {
            throw new AccessTokenException('invalid "expires_at" (syntax)');
        }

        // make sure it is actually a valid date
        try {
            $this->issuedAt = new DateTime($issuedAt);
        } catch (Exception $e) {
            throw new AccessTokenException(\sprintf('invalid "expires_at": %s', $e->getMessage()));
        }
    }

    /**
     * @param string $accessToken
     *
     * @return void
     */
    private function setAccessToken($accessToken)
    {
        // access-token = 1*VSCHAR
        // VSCHAR       = %x20-7E
        if (1 !== \preg_match('/^[\x20-\x7E]+$/', $accessToken)) {
            throw new AccessTokenException('invalid "access_token"');
        }
        $this->accessToken = $accessToken;
    }

    /**
     * @param string $tokenType
     *
     * @return void
     */
    private function setTokenType($tokenType)
    {
        if ('bearer' !== $tokenType && 'Bearer' !== $tokenType) {
            throw new AccessTokenException('unsupported "token_type"');
        }
        $this->tokenType = $tokenType;
    }

    /**
     * @param mixed|null $expiresIn
     *
     * @return void
     */
    private function setExpiresIn($expiresIn)
    {
        if (null !== $expiresIn) {
            if (false === \is_int($expiresIn)) {
                throw new AccessTokenException('"expires_in" must be int');
            }
            if (0 >= $expiresIn) {
                throw new AccessTokenException('invalid "expires_in"');
            }
        }
        $this->expiresIn = $expiresIn;
    }

    /**
     * @param string|null $refreshToken
     *
     * @return void
     */
    private function setRefreshToken($refreshToken)
    {
        if (null !== $refreshToken) {
            // refresh-token = 1*VSCHAR
            // VSCHAR        = %x20-7E
            if (1 !== \preg_match('/^[\x20-\x7E]+$/', $refreshToken)) {
                throw new AccessTokenException('invalid "refresh_token"');
            }
        }
        $this->refreshToken = $refreshToken;
    }

    /**
     * @param string|null $scope
     *
     * @return void
     */
    private function setScope($scope)
    {
        if (null !== $scope) {
            // scope       = scope-token *( SP scope-token )
            // scope-token = 1*NQCHAR
            // NQCHAR      = %x21 / %x23-5B / %x5D-7E
            foreach (\explode(' ', $scope) as $scopeToken) {
                if (1 !== \preg_match('/^[\x21\x23-\x5B\x5D-\x7E]+$/', $scopeToken)) {
                    throw new AccessTokenException('invalid "scope"');
                }
            }
        }
        $this->scope = $scope;
    }
}
