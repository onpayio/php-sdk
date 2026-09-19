<?php

declare(strict_types=1);

namespace OnPay;

use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Grant\RefreshToken;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use OnPay\API\Exception\ApiException;
use OnPay\API\Exception\TokenException;
use OnPay\API\Exception\ConnectionException;
use OnPay\API\GatewayService;
use OnPay\API\SubscriptionService;
use OnPay\API\TransactionService;
use OnPay\API\PaymentService;
use OnPay\API\Http\Request as HttpRequest;
use OnPay\API\Http\Response as HttpResponse;
use OnPay\Http\GuzzleClientAdapter;
use OnPay\Http\LoggingHttpClient;
use OnPay\Http\MessageUtil;
use OnPay\Http\Psr18HttpClient;
use OnPay\Http\RecordingHttpClientInterface;
use OnPay\Log\ErrorLogLogger;
use OnPay\OAuth\OnPayProvider;
use OnPay\OAuth\Psr17RequestFactory;
use OnPay\API\Util\DataReader;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Exception\NotFoundException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
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
     * @var OnPayProvider
     */
    protected OnPayProvider $oauth2Provider;

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
     * @var string
     */
    protected string $scope = OnPayProvider::DEFAULT_SCOPE;

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
     * installed packages (php-http/discovery). The SDK ships no HTTP client of
     * its own: if nothing can be discovered an \InvalidArgumentException is
     * thrown, install any PSR-18 client (and PSR-17 factories) or inject them.
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

        $this->tokenStorage = new InternalTokenStorage($tokenStorage);

        $this->logger = $logger ?? new ErrorLogLogger();
        try {
            $httpClient = $httpClient ?? Psr18ClientDiscovery::find();
            $requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
            $streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
        } catch (NotFoundException $e) {
            throw new \InvalidArgumentException(
                'No PSR-18 HTTP client and/or PSR-17 request/stream factory could be found. ' .
                'Install a PSR-18 client and a PSR-17 implementation, or pass them to the OnPayAPI constructor.',
                0,
                $e
            );
        }

        $this->httpClient = new LoggingHttpClient(new Psr18HttpClient($httpClient), $this->logger);

        $this->oauth2Provider = new OnPayProvider(
            [
                'clientId' => $this->clientId,
                'redirectUri' => $this->redirectUri,
                'urlAuthorize' => $authUrl,
                'urlAccessToken' => $this->baseUri . '/oauth2/access_token',
            ],
            [
                'httpClient' => new GuzzleClientAdapter($this->httpClient),
                'requestFactory' => new Psr17RequestFactory($requestFactory, $streamFactory),
            ]
        );

        $platform = array_key_exists('platform', $this->options)
            ? DataReader::stringOrNull($this->options, 'platform')
            : 'php-sdk' . '/' . self::SDK_VERSION;
        $this->platform = $platform ?? 'php-sdk' . '/' . self::SDK_VERSION;
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
        return $this->oauth2Provider->getAuthorizationUrl(['scope' => $this->scope]);
    }

    /**
     * Exchanges the authorization code for an access token and stores it.
     *
     * @param string $code
     * @throws TokenException
     * @throws ConnectionException
     */
    public function finishAuthorize(string $code): void {
        try {
            $token = $this->requestAccessToken('authorization_code', ['code' => $code], 'unable to obtain access_token');
        } catch (ClientExceptionInterface $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode(), $e);
        }

        $this->tokenStorage->storeAccessToken($token);
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
        return $this->send('GET', $url, [
            'headers' => ['User-Agent' => (string) $this->platform],
        ]);
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
        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => (string) $this->platform,
            ],
        ];

        try {
            $options['body'] = json_encode($postBody, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException $e) {
            throw new ApiException('Failed to encode request body as JSON: ' . $e->getMessage(), $e->getCode(), $e);
        }

        return $this->send('POST', $url, $options);
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
     * @return array<array-key, mixed>
     * @throws ApiException
     * @throws TokenException
     */
    private function handleResponse(ResponseInterface $response): array {
        $statusCode = $response->getStatusCode();
        $responseBody = (string) $response->getBody();

        if ($statusCode >= 200 && $statusCode < 300) {
            try {
                /** @var mixed $decoded */
                $decoded = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new ApiException('Failed to decode JSON body-response: ' . $e->getMessage(), $statusCode, $e);
            }
            if (!is_array($decoded)) {
                throw new ApiException('Expected a JSON object in the response body', $statusCode);
            }

            return $decoded;
        }

        $message = '';
        if ('' !== $responseBody && $response->getHeaderLine('Content-Type') === 'application/json') {
            try {
                /** @var mixed $decoded */
                $decoded = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new ApiException('Failed to decode JSON body-response: ' . $e->getMessage(), $statusCode, $e);
            }
            $body = (array) $decoded;
            if (isset($body['errors'][0]['message']) && is_string($body['errors'][0]['message'])) {
                $message = $body['errors'][0]['message'];
            }
        }
        if (403 === $statusCode) {
            throw new TokenException($message, $statusCode);
        }
        if (404 === $statusCode) {
            $message = 'Not found';
        }
        throw new ApiException($message, $statusCode);
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
     * Sends an authenticated API request, refreshing the stored token first when it
     * has expired. A 401 invalidates the token the same way a missing one does.
     *
     * @param array<string,mixed> $options headers/body for the request
     * @return array<array-key, mixed>
     * @throws ApiException
     * @throws TokenException
     * @throws ConnectionException
     */
    private function send(string $method, string $url, array $options): array {
        try {
            $token = $this->getValidAccessToken();
            if (null === $token) {
                throw new TokenException('Invalid response. Possible invalid token.');
            }

            $request = $this->oauth2Provider->getAuthenticatedRequest($method, $this->baseUri . '/v1/' . $url, $token, $options);
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode(), $e);
        }

        $this->setLastHttpRequest($this->httpClient->getLastRequest());
        $this->setLastHttpResponse($this->httpClient->getLastResponse());

        if (401 === $response->getStatusCode()) {
            throw new TokenException('Invalid response. Possible invalid token.');
        }

        return $this->handleResponse($response);
    }

    /**
     * Returns the stored token, refreshed if it has expired, or null when there is
     * no usable token (nothing stored, or expired without a refresh token).
     *
     * @throws TokenException
     * @throws ClientExceptionInterface
     */
    private function getValidAccessToken(): ?AccessTokenInterface {
        $token = $this->tokenStorage->getAccessToken();
        if (null === $token || !self::hasExpired($token)) {
            return $token;
        }

        $refreshToken = $token->getRefreshToken();
        if (null === $refreshToken) {
            return null;
        }

        $refreshed = $this->requestAccessToken(
            new RefreshToken(),
            ['refresh_token' => $refreshToken, 'scope' => $this->scope],
            'unable to refresh access_token'
        );
        // OnPay may omit the refresh token from a refresh response; keep the current one then.
        $refreshed = new AccessToken($refreshed->jsonSerialize() + ['refresh_token' => $refreshToken]);
        $this->tokenStorage->storeAccessToken($refreshed);

        return $refreshed;
    }

    /**
     * Calls the token endpoint, translating league's failures into the SDK's
     * TokenException. Transport failures (ClientExceptionInterface) pass through.
     *
     * @param string|AbstractGrant $grant
     * @param array<string,mixed> $options
     * @throws TokenException
     * @throws ClientExceptionInterface
     */
    private function requestAccessToken($grant, array $options, string $failure): AccessTokenInterface {
        try {
            return $this->oauth2Provider->getAccessToken($grant, $options);
        } catch (IdentityProviderException | \UnexpectedValueException $e) {
            throw new TokenException($failure . ': ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Tokens without an expiry (static API tokens) never expire. League stores
     * `expires_in: 0` as 0 and refuses to evaluate it, hence the emptiness check.
     */
    private static function hasExpired(AccessTokenInterface $token): bool {
        $expires = $token->getExpires();

        return null !== $expires && 0 !== $expires && $token->hasExpired();
    }

    private function setLastHttpRequest(?RequestInterface $request): void {
        $httpRequest = new HttpRequest();
        if (null !== $request) {
            $httpRequest->setMethod($request->getMethod());
            $httpRequest->setUri((string) $request->getUri());
            $httpRequest->setHeaders(MessageUtil::flattenHeaders($request));
            $httpRequest->setBody(MessageUtil::bodyOrNull($request));
        }
        $this->request = $httpRequest;
    }

    private function setLastHttpResponse(?ResponseInterface $response): void {
        $httpResponse = new HttpResponse();
        if (null !== $response) {
            $httpResponse->setStatusCode($response->getStatusCode());
            $httpResponse->setBody((string) $response->getBody());
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
