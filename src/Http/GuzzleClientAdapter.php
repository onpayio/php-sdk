<?php

declare(strict_types=1);

namespace OnPay\Http;

use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exposes a PSR-18 client through the Guzzle interface league/oauth2-client 2.x
 * requires, so the token endpoint traffic goes through the same (logging, recording)
 * client stack as the API traffic.
 *
 * Shim for league 2.x only: 3.x accepts a PSR-18 ClientInterface directly, at which
 * point this class goes away and OnPayAPI passes its client to the provider as is.
 *
 * league only ever calls send(); the async and convenience methods are not supported.
 *
 * @internal Shall not be used outside the library.
 */
final class GuzzleClientAdapter implements GuzzleClientInterface {
    private ClientInterface $client;

    public function __construct(ClientInterface $client) {
        $this->client = $client;
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface {
        return $this->client->sendRequest($request);
    }

    /**
     * @return PromiseInterface<ResponseInterface, mixed>
     */
    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface {
        throw new \LogicException(__METHOD__ . ' is not supported');
    }

    /**
     * @param string|\Psr\Http\Message\UriInterface $uri
     */
    public function request(string $method, $uri, array $options = []): ResponseInterface {
        throw new \LogicException(__METHOD__ . ' is not supported');
    }

    /**
     * @param string|\Psr\Http\Message\UriInterface $uri
     * @return PromiseInterface<ResponseInterface, mixed>
     */
    public function requestAsync(string $method, $uri, array $options = []): PromiseInterface {
        throw new \LogicException(__METHOD__ . ' is not supported');
    }

    /**
     * @return null
     */
    public function getConfig(?string $option = null) {
        return null;
    }
}
