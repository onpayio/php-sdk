<?php

namespace OnPay;

use fkooman\OAuth\Client\ErrorLogger;
use fkooman\OAuth\Client\Http\CurlHttpClient;
use fkooman\OAuth\Client\Http\Exception\CurlException;
use fkooman\OAuth\Client\Http\Request;
use fkooman\OAuth\Client\Http\Response;
use fkooman\OAuth\Client\Provider;
use OnPay\API\Exception\ApiException;
use OnPay\API\Exception\TokenException;
use OnPay\API\Exception\ConnectionException;
use OnPay\API\GatewayService;
use OnPay\API\SubscriptionService;
use OnPay\API\TransactionService;
use OnPay\API\Http\Request as HttpRequest;
use OnPay\API\Http\Response as HttpResponse;
use OnPay\OAuth\Client\OAuthClient;

class OnPayAPI {
    /**
     * @var InternalTokenStorage
     */
    protected $tokenStorage;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var Provider
     */
    protected $oauth2Provider;

    /**
     * @var OAuthClient
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
     * Not really used in the context of this implementation of fkooman/oauth2-client, however we set it as the same value for consistency.
     *
     * @var string
     */
    protected $userId = 'sdk_user';

    /**
     * @var string
     */
    protected $scope = 'full';

    /**
     * @var HttpRequest $request
     */
    protected $request;

    /**
     * @var HttpResponse $response
     */
    protected $response;

    /**
     * @var CurlHttpClientLogger
     */
    protected $httpClient;

    /**
     * OnPayAPI constructor.
     * @param \OnPay\TokenStorageInterface $tokenStorage
     * @param array $options
     */
    public function __construct(TokenStorageInterface $tokenStorage, array $options) {
        $this->tokenStorage = $tokenStorage;

        $defaultOptions = [
            'base_uri' => 'https://api.onpay.io',
            'base_authorize_uri' => 'https://manage.onpay.io',
        ];

        $requiredOptions = $this->getRequiredOptions($tokenStorage);

        $missing = array_diff_key(array_flip($requiredOptions), $options);
        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Required options not defined: ' . implode(', ', array_keys($missing))
            );
        }

        $this->options = array_merge($defaultOptions, $options);

        if(isset($this->options['gateway_id'])) {
            $gatewayId = intval($this->options['gateway_id']);
            if ($gatewayId === 0) {
                throw new \InvalidArgumentException('gateway_id must be numeric value');
            }
            $authUrl = $this->options['base_authorize_uri'] . '/' . $gatewayId . '/oauth2/authorize';
        } else {
            $authUrl = $this->options['base_authorize_uri'] . '/oauth2/authorize';
        }

        // Set redirect_uri to an empty value if none is sent
        if (!array_key_exists('redirect_uri', $this->options)) {
            $this->options['redirect_uri'] = '';
        }

        $this->tokenStorage = new InternalTokenStorage($tokenStorage, $authUrl, $options['client_id'], $this->scope);

        $this->oauth2Provider = new Provider(
            $this->options['client_id'],
            '',
            $authUrl,
            $this->options['base_uri'] . '/oauth2/access_token'
        );

        $this->httpClient = new CurlHttpClientLogger([], new ErrorLogger());
    }

    /**
     * @return OAuthClient
     */
    protected function getClient() {
        if (!isset($this->client)) {
            $this->client = new OAuthClient(
                $this->tokenStorage,
                $this->httpClient
            );
            // Construct the session allowing the implementation to be sessionless.
            $session = new Session();
            $session->set('state', $this->oauth2Provider->getProviderId());
            $session->set('provider_id', $this->oauth2Provider->getProviderId());
            $session->set('user_id', $this->userId);
            $session->set('redirect_uri', $this->options['redirect_uri']);
            $session->set('scope', $this->scope);
            $this->client->setSession($session);
        }

        return $this->client;
    }

    /**
     * Checks if we have a Token that looks valid.
     * If it looks valid, we'll attempt to ping the API.
     *
     * @return bool
     */
    public function isAuthorized() {
        // If we're able to ping the API, we're authorized.
        try {
            $this->ping();
            return true;
        } catch (TokenException $e) {
            return false;
        }
    }

    /**
     * Returns a URL the user should be redirected to, for authorizing.
     *
     * @return string
     */
    public function authorize() {
        return $this->getClient()->getAuthorizeUri($this->oauth2Provider, $this->userId, $this->scope, $this->options['redirect_uri']);
    }

    /**
     * @param string $code
     */
    public function finishAuthorize($code) {
        $this->getClient()->handleCallback(
            $this->oauth2Provider, $this->userId,
            [
                'code' => $code,
                'state' => \crypt($this->oauth2Provider->getProviderId(), 'state') // Value we're expecting from the Session
            ]
        );
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
     * @throws TokenException
     * @throws ConnectionException
     */
    public function get($url) {
        try {
            $request = Request::get($this->options['base_uri'] . '/v1/' . $url);
            $response = $this->getClient()->send(
                $this->oauth2Provider,
                $this->userId,
                $this->scope,
                $request
            );

            $this->setLastHttpRequest($this->httpClient->getLastRequest());
            $this->setLastHttpResponse($this->httpClient->getLastResponse());

            return $this->handleResponse($response);
        } catch (CurlException $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode(), $e);
        } catch (\fkooman\OAuth\Client\Exception\TokenException $e) {
            throw new TokenException($e->getMessage(), $e->getCode(), $e);
        } catch (\fkooman\OAUth\Client\Exception\AccessTokenException $e) {
            throw new TokenException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @internal
     * @param $url
     * @return mixed
     * @throws ApiException
     * @throws TokenException
     * @throws ConnectionException
     */
    public function post($url, $postBody = null) {
        try {
            $request = new Request(
                'POST',
                $this->options['base_uri'] . '/v1/' . $url,
                ['Content-Type' => 'application/json'],
                json_encode($postBody)
            );
            $response = $this->getClient()->send(
                $this->oauth2Provider,
                $this->userId,
                $this->scope,
                $request
            );

            $this->setLastHttpRequest($this->httpClient->getLastRequest());
            $this->setLastHttpResponse($this->httpClient->getLastResponse());

            return $this->handleResponse($response);
        } catch (CurlException $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode(), $e);
        } catch (\fkooman\OAuth\Client\Exception\TokenException $e) {
            throw new TokenException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param TokenStorageInterface $tokenStorage
     * @return string[]
     */
    private function getRequiredOptions(TokenStorageInterface $tokenStorage) {
        $options = [
            'client_id'
        ];

        if (!$tokenStorage instanceof StaticToken) {
            // Redirect URI is not needed for static tokens
            $options[] = 'redirect_uri';
        }

        return $options;
    }

    /**
     * @param Response|bool $response
     * @return mixed
     * @throws ApiException
     * @throws TokenException
     */
    private function handleResponse($response) {
        if (false === $response) {
            // When response is false we're dealing with an invalid token.
            throw new TokenException('Invalid response. Possible invalid token.');
        }

        if ($response->isOkay()) {
            return json_decode($response->getBody(), true);
        }

        $message = '';
        if ('' !== $response->getBody() && null !== $response->getBody()) {
            $body = json_decode($response->getBody(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ApiException('Failed to decode JSON body-response: ' . json_last_error_msg(), $response->getStatusCode());   
            }
            if (array_key_exists('errors', $body)) {
                $message = $body['errors'][0]['message'];
            }
        }
        if (403 === $response->getStatusCode()) {
            throw new TokenException($message, $response->getStatusCode());
        }
        throw new ApiException($message, $response->getStatusCode());
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

    /**
     * @param mixed $request
     */
    private function setLastHttpRequest($request) {
        $httpRequest = new HttpRequest();
        if ($request instanceof Request) {
            $httpRequest->setMethod($request->getMethod());
            $httpRequest->setUri($request->getUri());
            $httpRequest->setHeaders($request->getHeaders());
            $httpRequest->setBody($request->getBody());
        }
        $this->request = $httpRequest;
    }

    /**
     * @param mixed $response
     */
    private function setLastHttpResponse($response) {
        $httpResponse = new HttpResponse();
        if ($response instanceof Response) {
            $httpResponse->setStatusCode($response->getStatusCode());
            $httpResponse->setBody($response->getBody());
        }
        $this->response = $httpResponse;
    }

    /**
     * Returns the last HTTP Request send to the API
     * @return HttpRequest
     */
    public function getLastHttpRequest() {
        return $this->request;
    }

    /**
     * Returns the last HTTP Response received from the API
     * @return HttpResponse
     */
    public function getLastHttpResponse() {
        return $this->response;
    }
}
