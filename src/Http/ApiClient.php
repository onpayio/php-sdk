<?php

declare(strict_types=1);

namespace OnPay\Http;

use OnPay\API\Exception\ApiException;
use OnPay\API\Exception\ConnectionException;
use OnPay\API\Exception\TokenException;
use OnPay\API\Http\Request as HttpRequest;
use OnPay\API\Http\Response as HttpResponse;
use OnPay\Auth\TokenManager;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Owns the authenticated API call path: request construction, transport, response
 * handling and the last-request/response debug capture. Depends on {@see TokenManager}
 * for a valid, authenticated request. Consumed by the API service classes and the
 * facade {@see \OnPay\OnPayAPI}, which delegates get()/post() and the debug accessors here.
 *
 * @internal
 */
final class ApiClient {
    private RecordingHttpClientInterface $httpClient;

    private TokenManager $tokenManager;

    private string $baseUri;

    private ?string $platform;

    private ?HttpRequest $request = null;

    private ?HttpResponse $response = null;

    public function __construct(
        RecordingHttpClientInterface $httpClient,
        TokenManager $tokenManager,
        string $baseUri,
        ?string $platform
    ) {
        $this->httpClient = $httpClient;
        $this->tokenManager = $tokenManager;
        $this->baseUri = $baseUri;
        $this->platform = $platform;
    }

    /**
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
     * Returns the platform (User-Agent) string sent with every request, or null.
     */
    public function getPlatform(): ?string {
        return $this->platform;
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
            $request = $this->tokenManager->authenticateRequest($method, $this->baseUri . '/v1/' . $url, $options);
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
     * @return array<array-key, mixed>
     * @throws ApiException
     * @throws TokenException
     */
    private function handleResponse(ResponseInterface $response): array {
        $statusCode = $response->getStatusCode();
        $responseBody = (string) $response->getBody();

        if ($statusCode >= 200 && $statusCode < 300) {
            // A 2xx does not guarantee JSON: an error page can be served with a 2xx status.
            $contentType = $response->getHeaderLine('Content-Type');
            if ('' !== $contentType && !self::isJson($contentType)) {
                throw new ApiException(sprintf(
                    'Expected a JSON body, got %s with HTTP %d',
                    $contentType,
                    $statusCode
                ), $statusCode);
            }

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
        if ('' !== $responseBody && self::isJson($response->getHeaderLine('Content-Type'))) {
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
     * Matches `application/json` and its variants (`+json` suffixes, charset parameters).
     */
    private static function isJson(string $contentType): bool {
        return str_contains(strtolower($contentType), 'json');
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
     * Returns the last HTTP Request sent to the API
     */
    public function getLastHttpRequest(): ?HttpRequest {
        return $this->request;
    }

    /**
     * Returns the last HTTP Response received from the API
     */
    public function getLastHttpResponse(): ?HttpResponse {
        return $this->response;
    }
}
