<?php

declare(strict_types=1);

namespace OnPay\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Decorates a PSR-18 client so the last request/response can be inspected through
 * OnPayAPI's debug API. Consumers can plug in any PSR-18 client.
 *
 * @internal Shall not be used outside the library.
 */
class Psr18HttpClient implements RecordingHttpClientInterface {
    private ClientInterface $client;

    private ?RequestInterface $lastRequest = null;

    private ?ResponseInterface $lastResponse = null;

    public function __construct(ClientInterface $client) {
        $this->client = $client;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface {
        $this->lastRequest = $request;
        $this->lastResponse = $this->client->sendRequest($request);

        return $this->lastResponse;
    }

    public function getLastRequest(): ?RequestInterface {
        return $this->lastRequest;
    }

    public function getLastResponse(): ?ResponseInterface {
        return $this->lastResponse;
    }
}
