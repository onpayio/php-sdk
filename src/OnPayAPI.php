<?php

namespace OnPay;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use http\Exception\InvalidArgumentException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use OnPay\API\Exception\ApiException;
use OnPay\API\Exception\TokenException;
use OnPay\API\Exception\ConnectionException;
use OnPay\API\GatewayService;
use OnPay\API\SubscriptionService;
use OnPay\API\TransactionService;
use Psr\Http\Message\RequestInterface;

class OnPayAPI {
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var GenericProvider
     */
    protected $oauth2Provider;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var SubscriptionService
     */
    protected $subscriptionService;

    /**
     * @var GatewayService
     */
    protected $gatewayService;

    /**
     * OnPayAPI constructor.
     * @param TokenStorageInterface $tokenStorage
     * @param array $options
     */
    public function __construct(TokenStorageInterface $tokenStorage, array $options) {
        $this->tokenStorage = $tokenStorage;

        $defaultOptions = [
            'base_uri' => 'https://api.onpay.io',
            'base_authorize_uri' => 'https://manage.onpay.io',
        ];

        $requiredOptions = [
            'client_id',
            'redirect_uri',
        ];

        $missing = array_diff_key(array_flip($requiredOptions), $options);
        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Required options not defined: ' . implode(', ', array_keys($missing))
            );
        }

        $this->options = array_merge($defaultOptions, $options);

        $this->oauth2Provider = new GenericProvider([
            'clientId' => $this->options['client_id'],
            'redirectUri' => $this->options['redirect_uri'],
            'urlAuthorize' => $this->options['base_authorize_uri'] . '/oauth2/authorize',
            'urlAccessToken' => $this->options['base_uri'] . '/oauth2/access_token',
            'urlResourceOwnerDetails' => $this->options['base_uri'] . '/oauth2/resource_owner',
            'scopes' => ['full'],
        ]);

    }

    /**
     * @return AccessToken|null
     */
    protected function getAccessToken() {
        $storageToken = $this->tokenStorage->getToken();
        $options = json_decode($storageToken ? $storageToken : '', true);
        if (null !== $options) {
            $accessToken = new AccessToken($options);
            return $accessToken;
        }
        return null;
    }

    /**
     * @return Client
     */
    protected function getClient() {
        if (!isset($this->client)) {
            $this->client = new Client();
        }

        return $this->client;
    }

    /**
     * @param RequestInterface $request
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function sendRequest(RequestInterface $request) {
        $response = $this->getClient()->send($request);

        $result = json_decode($response->getBody()->getContents(), true);

        return $result;
    }

    /**
     * @throws TokenException
     * @throws ConnectionException
     */
    protected function refreshToken() {
        $oldAccessToken = $this->getAccessToken();
        try {
            $accessToken = $this->oauth2Provider->getAccessToken('refresh_token', [
                'refresh_token' => $oldAccessToken->getRefreshToken(),
            ]);
        } catch (IdentityProviderException $e) {
            throw new TokenException('Token is invalid', 0);
        } catch (\UnexpectedValueException $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode());
        } catch (ConnectException $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode());
        }
        $this->tokenStorage->saveToken(json_encode($accessToken));
    }

    /**
     * Checks if we have a Token that looks valid.
     * If it is expired, will attempt to renew it.
     *
     * @return bool
     */
    public function isAuthorized() {
        $accessToken = $this->getAccessToken();
        if (null === $accessToken) {
            return false;
        }

        if ($accessToken->hasExpired()) {
            // Token expired, attempt to refresh it
            try {
                $this->refreshToken();
            } catch (TokenException $e) {
                return false;
            } catch (ConnectionException $e) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns a URL the user should be redirected to, for authorizing.
     *
     * @return string
     */
    public function authorize() {
        return $this->oauth2Provider->getAuthorizationUrl();
    }

    /**
     * @param string $code
     * @throws ConnectionException
     */
    public function finishAuthorize(string $code) {
        try {
            $accessToken = $this->oauth2Provider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);
        } catch (\UnexpectedValueException $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode());
        } catch (ConnectException $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode());
        }

        $this->tokenStorage->saveToken(json_encode($accessToken));
    }

    /**
     * Simple method that just checks if API requests can be made
     *
     * @return string
     * @throws ApiException
     */
    public function ping() {
        return $this->get('ping');
    }

    /**
     * @internal
     * @param $url
     * @return mixed
     * @throws ApiException
     * @throws ConnectionException
     */
    public function get($url) {
        $request = $this->oauth2Provider->getAuthenticatedRequest(
            'GET',
            $this->options['base_uri'] . '/v1/' . $url,
            $this->getAccessToken()
        );

        try {
            $response = $this->sendRequest($request);
        } catch (ClientException $e) {
            throw new ApiException($e->getResponse()->getReasonPhrase(), $e->getCode());
        } catch (ConnectException $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode());
        }

        return $response;
    }

    /**
     * @internal
     * @param $url
     * @return mixed
     * @throws ApiException
     * @throws ConnectionException
     */
    public function post($url, $json = null) {
        $request = $this->oauth2Provider->getAuthenticatedRequest(
            'POST',
            $this->options['base_uri'] . '/v1/' . $url,
            $this->getAccessToken(),
            [
                'body' => json_encode($json),
            ]
        );

        try {
            $response = $this->sendRequest($request);
        } catch (ClientException $e) {
            throw new ApiException($e->getResponse()->getReasonPhrase(), $e->getCode());
        } catch (ConnectException $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode());
        }

        return $response;
    }

    /**
     * @return TransactionService
     */
    public function transaction() {
        if (!isset($this->transactionService)) {
            $this->transactionService = new TransactionService($this);
        }
        return $this->transactionService;
    }

    /**
     * @return SubscriptionService
     */
    public function subscription() {
        if(!isset($this->subscriptionService)) {
            $this->subscriptionService = new SubscriptionService($this);
        }
        return $this->subscriptionService;
    }

    /**
     * @return GatewayService
     */
    public function gateway() {
        if(!isset($this->gatewayService)) {
            $this->gatewayService = new GatewayService($this);
        }
        return $this->gatewayService;
    }
}
