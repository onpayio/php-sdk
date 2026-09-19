<?php

namespace OnPay\Http;

use OnPay\OAuth\Client\Http\Exception\CurlException;
use OnPay\OAuth\Client\Http\Request;
use OnPay\OAuth\Client\Http\Response;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Adapts a PSR-18 HTTP client (with PSR-17 factories) to the SDK's internal
 * HttpClientInterface, so consumers can plug in any PSR-18 client.
 *
 * The SDK's transport layer speaks OnPay\OAuth\Client\Http\{Request,Response};
 * this adapter converts to/from PSR-7 on the way in/out. It deliberately records
 * the OnPay DTOs (not PSR-7 messages) because OnPayAPI's last-request/response
 * capture only understands those types.
 */
class Psr18HttpClient implements RecordingHttpClientInterface {
    /** @var ClientInterface */
    private $client;

    /** @var RequestFactoryInterface */
    private $requestFactory;

    /** @var StreamFactoryInterface */
    private $streamFactory;

    /** @var Request|null */
    private $lastRequest;

    /** @var Response|null */
    private $lastResponse;

    public function __construct(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function send(Request $request) {
        // Record the OnPay request (already carries the real headers, incl. the
        // Bearer token added upstream by OAuthClient::send()).
        $this->lastRequest = $request;

        $psrRequest = $this->requestFactory->createRequest($request->getMethod(), $request->getUri());
        foreach ($request->getHeaders() as $name => $value) {
            $psrRequest = $psrRequest->withHeader($name, $value);
        }
        $body = $request->getBody();
        if (null !== $body) {
            // Mirror CurlHttpClient, which sets CURLOPT_POSTFIELDS whenever a body is
            // present (including an empty string), and omits it only when null.
            $psrRequest = $psrRequest->withBody($this->streamFactory->createStream($body));
        }

        try {
            $psrResponse = $this->client->sendRequest($psrRequest);
        } catch (ClientExceptionInterface $e) {
            // Map any PSR-18 transport failure onto the exception the SDK already
            // translates into OnPay\API\Exception\ConnectionException.
            throw new CurlException($e->getMessage(), $e->getCode(), $e);
        }

        $response = new Response(
            $psrResponse->getStatusCode(),
            (string) $psrResponse->getBody(),
            $this->flattenHeaders($psrResponse->getHeaders())
        );
        $this->lastResponse = $response;

        return $response;
    }

    /**
     * @return Request|null
     */
    public function getLastRequest() {
        return $this->lastRequest;
    }

    /**
     * @return Response|null
     */
    public function getLastResponse() {
        return $this->lastResponse;
    }

    /**
     * Flatten PSR-7 headers (name => list of values) into the flat name => value
     * map the SDK's Response expects, matching the shape CurlHttpClient produces.
     *
     * @param array<array-key, array<array-key, string>> $headers
     *
     * @return array<string, string>
     */
    private function flattenHeaders(array $headers) {
        $flat = [];
        foreach ($headers as $name => $values) {
            $flat[(string) $name] = \implode(', ', $values);
        }

        return $flat;
    }
}
