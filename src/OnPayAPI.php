<?php

namespace OnPay;

use OnPay\OAuth\Client\Http\CurlHttpClient;
use OnPay\OAuth\Client\Http\Exception\CurlException;
use OnPay\OAuth\Client\Http\Response;
use OnPay\OAuth\Client\Provider;
use OnPay\OAuth\Client\Http\Request;
use OnPay\API\Exception\ApiException;
use OnPay\API\Exception\TokenException;
use OnPay\API\Exception\ConnectionException;
use OnPay\API\GatewayService;
use OnPay\API\SubscriptionService;
use OnPay\API\TransactionService;
use OnPay\API\PaymentService;
use OnPay\API\Http\Request as HttpRequest;
use OnPay\API\Http\Response as HttpResponse;
use OnPay\OAuth\Client\OAuthClient;
use OnPay\Http\Psr18HttpClient;
use OnPay\Http\RecordingHttpClientInterface;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Exception\NotFoundException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class OnPayAPI {
    const SDK_VERSION = '1.0.39';

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
     * @var PaymentService
     */
    protected $paymentService;

    /**
     * @var GatewayService
     */
    protected $gatewayService;

    /**
     * Not really used in the context of this implementation of OnPay/oauth2-client, however we set it as the same value for consistency.
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
     * @var RecordingHttpClientInterface
     */
    protected $httpClient;

    /**
     * @var string
     */
    protected $platform;

    /**
     * OnPayAPI constructor.
     *
     * The HTTP client is optional. When a PSR-18 client is supplied it is used
     * for all API and OAuth traffic (PSR-17 factories are required to build the
     * requests; any that are omitted are auto-discovered). When no client is
     * supplied, a PSR-18 client + factories are auto-discovered from the
     * environment, falling back to the bundled cURL client if none are installed.
     *
     * @param \OnPay\TokenStorageInterface $tokenStorage
     * @param array $options
     * @param ClientInterface|null $httpClient
     * @param RequestFactoryInterface|null $requestFactory
     * @param StreamFactoryInterface|null $streamFactory
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        array $options,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ) {
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
            $gatewayId = (string) $this->options['gateway_id'];
            if ($gatewayId === '' || !preg_match('/^[A-Z0-9]+$/', $gatewayId)) {
                throw new \InvalidArgumentException('gateway_id must be a non-empty alphanumeric value');
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

        $this->httpClient = $this->resolveHttpClient($httpClient, $requestFactory, $streamFactory);

        if (array_key_exists('platform', $this->options)) {
            $this->platform = $this->options['platform'];
        } else {
            $this->platform = 'php-sdk' . '/' . self::SDK_VERSION;
        }
    }

    /**
     * Resolves the HTTP client used for all API and OAuth traffic.
     *
     * Order: (1) an explicitly injected PSR-18 client (discovering any missing
     * PSR-17 factory); (2) a PSR-18 client + factories discovered from the
     * environment; (3) the bundled cURL client as a last-resort default.
     *
     * @param ClientInterface|null $httpClient
     * @param RequestFactoryInterface|null $requestFactory
     * @param StreamFactoryInterface|null $streamFactory
     * @return RecordingHttpClientInterface
     */
    private function resolveHttpClient(
        ?ClientInterface $httpClient,
        ?RequestFactoryInterface $requestFactory,
        ?StreamFactoryInterface $streamFactory
    ) {
        if (null !== $httpClient) {
            try {
                $requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
                $streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
            } catch (NotFoundException $e) {
                throw new \InvalidArgumentException(
                    'A PSR-18 HTTP client was provided but no PSR-17 request/stream factory could be found. ' .
                    'Provide a RequestFactoryInterface and StreamFactoryInterface, or install a PSR-17 implementation.',
                    0,
                    $e
                );
            }

            return new Psr18HttpClient($httpClient, $requestFactory, $streamFactory);
        }

        try {
            $discoveredClient = Psr18ClientDiscovery::find();
            $requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
            $streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();

            return new Psr18HttpClient($discoveredClient, $requestFactory, $streamFactory);
        } catch (NotFoundException $e) {
            // No PSR-18/17 stack available — fall back to the bundled cURL client.
            return new CurlHttpClientLogger([]);
        }
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
            $session->set('code_verifier', '');
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
     * Returns the platform value set.
     * @return string
     */
    public function getPlatform() {
        return $this->platform;
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
            $request = Request::get( $this->options['base_uri'] . '/v1/' . $url, [], ['User-Agent' => $this->platform]);
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
        } catch (\OnPay\OAuth\Client\Exception\TokenException $e) {
            throw new TokenException($e->getMessage(), $e->getCode(), $e);
        } catch (\OnPay\OAUth\Client\Exception\AccessTokenException $e) {
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
                [
                    'Content-Type' => 'application/json',
                    'User-Agent' => $this->platform,
                ],
                json_encode($postBody, JSON_UNESCAPED_SLASHES)
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
        } catch (\OnPay\OAuth\Client\Exception\TokenException $e) {
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
        if ('' !== $response->getBody() && null !== $response->getBody() && $response->getHeader('content-type') === 'application/json') {
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
        if (404 === $response->getStatusCode()) {
            $message = 'Not found';
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
     * @return PaymentService
     */
    public function payment() {
        if (!isset($this->paymentService)) {
            $this->paymentService = new PaymentService($this);
        }
        return $this->paymentService;
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
