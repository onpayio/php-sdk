<?php

declare(strict_types=1);

namespace OnPay;

use OnPay\API\Exception\ApiException;
use OnPay\API\Exception\TokenException;
use OnPay\API\Exception\ConnectionException;
use OnPay\API\GatewayService;
use OnPay\API\SubscriptionService;
use OnPay\API\TransactionService;
use OnPay\API\PaymentService;
use OnPay\API\Http\Request as HttpRequest;
use OnPay\API\Http\Response as HttpResponse;
use OnPay\Auth\TokenManager;
use OnPay\Http\ApiClient;
use OnPay\Http\GuzzleClientAdapter;
use OnPay\Http\LoggingHttpClient;
use OnPay\Http\Psr18HttpClient;
use OnPay\Log\ErrorLogLogger;
use OnPay\OAuth\OnPayProvider;
use OnPay\OAuth\Psr17RequestFactory;
use OnPay\API\Util\DataReader;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Exception\NotFoundException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Public facade for the OnPay API. Validates options and wires up the collaborators,
 * then delegates: authorization/token work to {@see \OnPay\Auth\TokenManager} and the
 * API call path (plus the last-request/response debug accessors) to
 * {@see \OnPay\Http\ApiClient}.
 */
final class OnPayAPI {
    const SDK_VERSION = '1.0.39';

    /**
     * @var array<array-key, mixed>
     */
    private array $options = [];

    /**
     * @var TransactionService|null
     */
    private ?TransactionService $transactionService = null;

    /**
     * @var SubscriptionService|null
     */
    private ?SubscriptionService $subscriptionService = null;

    /**
     * @var PaymentService|null
     */
    private ?PaymentService $paymentService = null;

    /**
     * @var GatewayService|null
     */
    private ?GatewayService $gatewayService = null;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    private TokenManager $tokenManager;

    private ApiClient $apiClient;

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
     * @param AuthStateStorageInterface|null $authStateStorage persists the OAuth CSRF
     *        `state` and PKCE `code_verifier` across the redirect; when omitted, state
     *        verification and PKCE are disabled and using the OAuth flow is deprecated.
     * @param ClientInterface|null $httpClient
     * @param RequestFactoryInterface|null $requestFactory
     * @param StreamFactoryInterface|null $streamFactory
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        array $options,
        ?AuthStateStorageInterface $authStateStorage = null,
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

        $baseUri = $this->requireStringOption('base_uri');
        $baseAuthorizeUri = $this->requireStringOption('base_authorize_uri');
        $clientId = $this->requireStringOption('client_id');

        if(isset($this->options['gateway_id'])) {
            $gatewayId = (string) $this->options['gateway_id'];
            if ($gatewayId === '' || !preg_match('/^[A-Z0-9]+$/', $gatewayId)) {
                throw new \InvalidArgumentException('gateway_id must be a non-empty alphanumeric value');
            }
            $authUrl = $baseAuthorizeUri . '/' . $gatewayId . '/oauth2/authorize';
        } else {
            $authUrl = $baseAuthorizeUri . '/oauth2/authorize';
        }

        // Set redirect_uri to an empty value if none is sent
        if (!array_key_exists('redirect_uri', $this->options)) {
            $this->options['redirect_uri'] = '';
        }
        $redirectUri = $this->requireStringOption('redirect_uri');

        $tokenStorage = new InternalTokenStorage($tokenStorage);

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

        $recordingHttpClient = new LoggingHttpClient(new Psr18HttpClient($httpClient), $this->logger);

        $oauth2Provider = new OnPayProvider(
            [
                'clientId' => $clientId,
                'redirectUri' => $redirectUri,
                'urlAuthorize' => $authUrl,
                'urlAccessToken' => $baseUri . '/oauth2/access_token',
                // PKCE only works when the verifier can be persisted across the redirect.
                'pkceEnabled' => null !== $authStateStorage,
            ],
            [
                'httpClient' => new GuzzleClientAdapter($recordingHttpClient),
                'requestFactory' => new Psr17RequestFactory($requestFactory, $streamFactory),
            ]
        );

        $platform = array_key_exists('platform', $this->options)
            ? DataReader::stringOrNull($this->options, 'platform')
            : 'php-sdk' . '/' . self::SDK_VERSION;
        $platform = $platform ?? 'php-sdk' . '/' . self::SDK_VERSION;

        $this->tokenManager = new TokenManager(
            $oauth2Provider,
            $tokenStorage,
            $authStateStorage,
            OnPayProvider::DEFAULT_SCOPE
        );
        $this->apiClient = new ApiClient($recordingHttpClient, $this->tokenManager, $baseUri, $platform);
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
        return $this->apiClient->getPlatform();
    }

    /**
     * Returns a URL the user should be redirected to, for authorizing.
     *
     * @return string
     */
    public function authorize(): string {
        return $this->tokenManager->authorize();
    }

    /**
     * Exchanges the authorization code for an access token and stores it.
     *
     * @param string $code
     * @param string|null $returnedState the `state` query parameter from the callback
     * @throws TokenException on a state mismatch or a token endpoint failure
     * @throws ConnectionException on a transport failure
     */
    public function finishAuthorize(string $code, ?string $returnedState = null): void {
        $this->tokenManager->finishAuthorize($code, $returnedState);
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
        return $this->apiClient->get($url);
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
        return $this->apiClient->post($url, $postBody);
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
     * @return TransactionService
     */
    public function transaction(): TransactionService {
        if (null === $this->transactionService) {
            $this->transactionService = new TransactionService($this->apiClient);
        }
        return $this->transactionService;
    }

    /**
     * @return SubscriptionService
     */
    public function subscription(): SubscriptionService {
        if(null === $this->subscriptionService) {
            $this->subscriptionService = new SubscriptionService($this->apiClient);
        }
        return $this->subscriptionService;
    }

    /**
     * @return PaymentService
     */
    public function payment(): PaymentService {
        if (null === $this->paymentService) {
            $this->paymentService = new PaymentService($this->apiClient);
        }
        return $this->paymentService;
    }

    /**
     * @return GatewayService
     */
    public function gateway(): GatewayService {
        if(null === $this->gatewayService) {
            $this->gatewayService = new GatewayService($this->apiClient);
        }
        return $this->gatewayService;
    }

    /**
     * Returns the last HTTP Request send to the API
     * @return HttpRequest|null
     */
    public function getLastHttpRequest(): ?HttpRequest {
        return $this->apiClient->getLastHttpRequest();
    }

    /**
     * Returns the last HTTP Response received from the API
     * @return HttpResponse|null
     */
    public function getLastHttpResponse(): ?HttpResponse {
        return $this->apiClient->getLastHttpResponse();
    }
}
