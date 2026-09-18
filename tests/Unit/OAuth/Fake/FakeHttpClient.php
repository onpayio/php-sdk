<?php

namespace Tests\Unit\OAuth\Fake;

use OnPay\OAuth\Client\Http\HttpClientInterface;
use OnPay\OAuth\Client\Http\Request;
use OnPay\OAuth\Client\Http\Response;

/**
 * In-memory HttpClientInterface returning canned Response DTOs in order.
 *
 * Records every Request it receives so tests can assert on the constructed
 * token/authorization requests without touching the network.
 */
class FakeHttpClient implements HttpClientInterface
{
    /** @var Request[] */
    public array $requests = [];

    /** @var Response[] */
    private array $responses;

    private int $index = 0;

    /**
     * @param Response[] $responses queue of responses returned in order
     */
    public function __construct(array $responses = [])
    {
        $this->responses = $responses;
    }

    public function send(Request $request): Response
    {
        $this->requests[] = $request;

        if (!isset($this->responses[$this->index])) {
            throw new \RuntimeException('FakeHttpClient: no canned response for call #' . $this->index);
        }

        return $this->responses[$this->index++];
    }

    public function getLastRequest(): Request
    {
        return $this->requests[\count($this->requests) - 1];
    }
}
