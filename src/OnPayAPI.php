<?php

declare(strict_types=1);

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
use OnPay\Http\LoggingHttpClient;
use OnPay\Http\Psr18HttpClient;
use OnPay\Http\RecordingHttpClientInterface;
use OnPay\Log\ErrorLogLogger;
use OnPay\API\Util\DataReader;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Exception\NotFoundException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

class OnPayAPI {
    const SDK_VERSION = '1.0.39';

    /**
     * @var InternalTokenStorage
     */
    protected InternalTokenStorage $tokenStorage;

    /**
     * @var array<array-key, mixed>
     */
    protected array $options = [];

    /**
     * @var string
     */
    protected string $baseUri;

    /**
     * @var string
     */
    protected string $baseAuthorizeUri;

    /**
     * @var string
     */
    protected string $clientId;

    /**
     * @var string
     */
    protected string $redirectUri;

    /**
     * @var Provider
     */
    protected Provider $oauth2Provider;

    /**
     * @var OAuthClient|null
     */
    protected ?OAuthClient $client = null;

    /**
     * @var TransactionService|null
     */
    protected ?TransactionService $transactionService = null;

    /**
     * @var SubscriptionService|null
     */
    protected ?SubscriptionService $subscriptionService = null;

    /**
     * @var PaymentService|null
     */
    protected ?PaymentService $paymentService = null;

    /**
     * @var GatewayService|null
     */
    protected ?GatewayService $gatewayService = null;

    /**
     * Not really used in the context of this implementation of OnPay/oauth2-client, however we set it as the same value for consistency.
     *
     * @var string
     */
    protected string $userId = 'sdk_user';

    /**
     * @var string
     */
    protected string $scope = 'full';

    /**
     * @var HttpRequest|null $request
     */
    protected ?HttpRequest $request = null;

    /**
     * @var HttpResponse|null $response
     */
    protected ?HttpResponse $response = null;

    /**
     * @var RecordingHttpClientInterface
     */
    protected RecordingHttpClientInterface $httpClient;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var string|null
     */
    protected ?string $platform = null;

    /**
     * OnPayAPI constructor.
     *
     * The HTTP client is optional. When a PSR-18 client is supplied it is used
     * for all API and OAuth traffic (PSR-17 factories are required to build the
     * requests; any that are omitted are auto-discovered). When no client is
     * supplied, a PSR-18 client + factories are auto-discovered from the
     * environment, falling back to the bundled cURL client if none are installed.
     *
     * The logger is optional. Failed API and OAuth round trips (non-2xx responses
     * and transport errors) are reported to it with credentials and cardholder
     * data redacted; successful ones at debug level. When no logger is supplied,
     * warnings and errors are written to PHP's error_log(). Pass a
     * {@see \Psr\Log\NullLogger} to silence the SDK entirely.
     *
     * @param \OnPay\TokenStorageInterface $tokenStorage
     * @param array $options
     * @param ClientInterface|null $httpClient
     * @param RequestFactoryInterface|null $requestFactory
     * @param StreamFactoryInterface|null $streamFactory
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        array $options,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?LoggerInterface $logger = null
    ) {
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

        $this->baseUri = $this->requireStringOption('base_uri');
        $this->baseAuthorizeUri = $this->requireStringOption('base_authorize_uri');
        $this->clientId = $this->requireStringOption('client_id');

        if(isset($this->options['gateway_id'])) {
            $gatewayId = (string) $this->options['gateway_id'];
            if ($gatewayId === '' || !preg_match('/^[A-Z0-9]+$/', $gatewayId)) {
                throw new \InvalidArgumentException('gateway_id must be a non-empty alphanumeric value');
            }
            $authUrl = $this->baseAuthorizeUri . '/' . $gatewayId . '/oauth2/authorize';
        } else {
            $authUrl = $this->baseAuthorizeUri . '/oauth2/authorize';
        }

        // Set redirect_uri to an empty value if none is sent
        if (!array_key_exists('redirect_uri', $this->options)) {
            $this->options['redirect_uri'] = '';
        }
        $this->redirectUri = $this->requireStringOption('redirect_uri');

        $this->tokenStorage = new InternalTokenStorage($tokenStorage, $authUrl, $this->clientId, $this->scope);

        $this->oauth2Provider = new Provider(
            $this->clientId,
            '',
            $authUrl,
            $this->baseUri . '/oauth2/access_token'
        );

        $this->logger = $logger ?? new ErrorLogLogger();
        $this->httpClient = new LoggingHttpClient(
            $this->resolveHttpClient($httpClient, $requestFactory, $streamFactory),
            $this->logger
        );

        $platform = array_key_exists('platform', $this->options)
            ? DataReader::stringOrNull($this->options, 'platform')
            : 'php-sdk' . '/' . self::SDK_VERSION;
        $this->platform = $platform ?? 'php-sdk' . '/' . self::SDK_VERSION;
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
    ): RecordingHttpClientInterface {
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
    protected function getClient(): OAuthClient {
        if (null === $this->client) {
            $this->client = new OAuthClient(
                $this->tokenStorage,
                $this->httpClient
            );
            // Construct the session allowing the implementation to be sessionless.
            $session = new Session();
            $session->set('state', $this->oauth2Provider->getProviderId());
            $session->set('provider_id', $this->oauth2Provider->getProviderId());
            $session->set('user_id', $this->userId);
            $session->set('redirect_uri', $this->redirectUri);
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
    public function isAuthorized(): bool {
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
     * @return string|null
     */
    public function getPlatform(): ?string {
        return $this->platform;
    }

    /**
     * Returns a URL the user should be redirected to, for authorizing.
     *
     * @return string
     */
    public function authorize(): string {
        return $this->getClient()->getAuthorizeUri($this->oauth2Provider, $this->userId, $this->scope, $this->redirectUri);
    }

    /**
     * @param string $code
     */
    public function finishAuthorize(string $code): void {
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
     * @return array<array-key, mixed>
     * @throws ApiException
     */
    public function ping(): array {
        return $this->get('ping');
    }

    /**
     * @internal
     * @param string $url
     * @return array<array-key, mixed>
     * @throws ApiException
     * @throws TokenException
     * @throws ConnectionException
     */
    public function get(string $url): array {
        try {
            $request = Request::get( $this->baseUri . '/v1/' . $url, [], ['User-Agent' => (string) $this->platform]);
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
        } catch (\OnPay\OAuth\Client\Exception\AccessTokenException $e) {
            throw new TokenException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @internal
     * @param string $url
     * @param mixed $postBody
     * @return array<array-key, mixed>
     * @throws ApiException
     * @throws TokenException
     * @throws ConnectionException
     */
    public function post(string $url, mixed $postBody = null): array {
        try {
            try {
                $encodedBody = json_encode($postBody, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            } catch (\JsonException $e) {
                throw new ApiException('Failed to encode request body as JSON: ' . $e->getMessage(), $e->getCode(), $e);
            }
            $request = new Request(
                'POST',
                $this->baseUri . '/v1/' . $url,
                [
                    'Content-Type' => 'application/json',
                    'User-Agent' => (string) $this->platform,
                ],
                $encodedBody
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
        } catch (\OnPay\OAuth\Client\Exception\AccessTokenException $e) {
            throw new TokenException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return string
     */
    private function requireStringOption(string $name): string {
        $value = DataReader::stringOrNull($this->options, $name);
        if (null === $value) {
            throw new \InvalidArgumentException(\sprintf('Option "%s" must be a string', $name));
        }

        return $value;
    }

    /**
     * @param TokenStorageInterface $tokenStorage
     * @return string[]
     */
    private function getRequiredOptions(TokenStorageInterface $tokenStorage): array {
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
     * @param Response|false $response
     * @return array<array-key, mixed>
     * @throws ApiException
     * @throws TokenException
     */
    private function handleResponse(Response|false $response): array {
        if (false === $response) {
            // When response is false we're dealing with an invalid token.
            throw new TokenException('Invalid response. Possible invalid token.');
        }

        if ($response->isOkay()) {
            try {
                /** @var mixed $decoded */
                $decoded = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new ApiException('Failed to decode JSON body-response: ' . $e->getMessage(), $response->getStatusCode(), $e);
            }
            if (!is_array($decoded)) {
                throw new ApiException('Expected a JSON object in the response body', $response->getStatusCode());
            }

            return $decoded;
        }

        $message = '';
        if ('' !== $response->getBody() && $response->getHeader('content-type') === 'application/json') {
            try {
                /** @var mixed $decoded */
                $decoded = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new ApiException('Failed to decode JSON body-response: ' . $e->getMessage(), $response->getStatusCode(), $e);
            }
            $body = (array) $decoded;
            if (isset($body['errors'][0]['message']) && is_string($body['errors'][0]['message'])) {
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
    public function transaction(): TransactionService {
        if (null === $this->transactionService) {
            $this->transactionService = new TransactionService($this);
        }
        return $this->transactionService;
    }

    /**
     * @return SubscriptionService
     */
    public function subscription(): SubscriptionService {
        if(null === $this->subscriptionService) {
            $this->subscriptionService = new SubscriptionService($this);
        }
        return $this->subscriptionService;
    }

    /**
     * @return PaymentService
     */
    public function payment(): PaymentService {
        if (null === $this->paymentService) {
            $this->paymentService = new PaymentService($this);
        }
        return $this->paymentService;
    }

    /**
     * @return GatewayService
     */
    public function gateway(): GatewayService {
        if(null === $this->gatewayService) {
            $this->gatewayService = new GatewayService($this);
        }
        return $this->gatewayService;
    }

    /**
     * @param mixed $request
     */
    private function setLastHttpRequest(mixed $request): void {
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
    private function setLastHttpResponse(mixed $response): void {
        $httpResponse = new HttpResponse();
        if ($response instanceof Response) {
            $httpResponse->setStatusCode($response->getStatusCode());
            $httpResponse->setBody($response->getBody());
        }
        $this->response = $httpResponse;
    }

    /**
     * Returns the last HTTP Request send to the API
     * @return HttpRequest|null
     */
    public function getLastHttpRequest(): ?HttpRequest {
        return $this->request;
    }

    /**
     * Returns the last HTTP Response received from the API
     * @return HttpResponse|null
     */
    public function getLastHttpResponse(): ?HttpResponse {
        return $this->response;
    }
}
