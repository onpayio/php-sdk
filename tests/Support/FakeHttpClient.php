<?php

namespace Tests\Support;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A fake PSR-18 HTTP client for the SDK test harness.
 *
 * Inject it into OnPayAPI via the constructor's PSR-18 client argument. The SDK wraps
 * it in {@see \OnPay\Http\Psr18HttpClient}, so the debug API
 * ({@see \OnPay\OnPayAPI::getLastHttpRequest()} / getLastHttpResponse()) still populates.
 *
 * Responses are "arranged" up front and matched against incoming requests by HTTP
 * method and a path/target substring. It records every request it receives so tests can
 * assert the outgoing wire shape (final URI, Authorization header, body), and it fails
 * loudly on an unexpected request or an unconsumed arrangement.
 */
class FakeHttpClient implements ClientInterface
{
    /**
     * @var array<int, array{method: ?string, match: ?string, response: ResponseInterface}>
     */
    private array $queue = [];

    /**
     * @var RequestInterface[]
     */
    private array $recordedRequests = [];

    /**
     * Arrange a response to return for the next matching request.
     *
     * @param string|null $method HTTP method to match (case-insensitive), or null for any.
     * @param string|null $match  Substring matched against the request target (path + query),
     *                            e.g. "transaction/123" or "gateway/information"; null for any.
     */
    public function willReturn(ResponseInterface $response, ?string $method = null, ?string $match = null): self
    {
        $this->queue[] = [
            'method' => null !== $method ? strtoupper($method) : null,
            'match' => $match,
            'response' => $response,
        ];

        return $this;
    }

    /**
     * Arrange a JSON response (sets the exact Content-Type the SDK's error path expects).
     *
     * @param array<mixed> $body
     */
    public function willReturnJson(array $body, int $status = 200, ?string $method = null, ?string $match = null): self
    {
        return $this->willReturn(
            new Response($status, ['Content-Type' => 'application/json'], json_encode($body, JSON_UNESCAPED_SLASHES)),
            $method,
            $match
        );
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->recordedRequests[] = $request;

        $method = strtoupper($request->getMethod());
        $target = $this->targetOf($request);

        foreach ($this->queue as $index => $entry) {
            $methodOk = null === $entry['method'] || $entry['method'] === $method;
            $matchOk = null === $entry['match'] || str_contains($target, $entry['match']);
            if ($methodOk && $matchOk) {
                unset($this->queue[$index]);

                return $entry['response'];
            }
        }

        throw new \RuntimeException(sprintf(
            'FakeHttpClient: no canned response arranged for %s %s',
            $method,
            (string) $request->getUri()
        ));
    }

    public function getLastRequest(): ?RequestInterface
    {
        $last = end($this->recordedRequests);
        $this->recordedRequests && reset($this->recordedRequests);

        return false === $last ? null : $last;
    }

    /**
     * @return RequestInterface[]
     */
    public function getRecordedRequests(): array
    {
        return $this->recordedRequests;
    }

    /**
     * Assert every arranged response was consumed. Call from tearDown so an unused
     * fixture fails the test instead of passing silently.
     */
    public function assertAllResponsesConsumed(): void
    {
        if ([] !== $this->queue) {
            throw new \RuntimeException(sprintf(
                'FakeHttpClient: %d arranged response(s) were never consumed',
                count($this->queue)
            ));
        }
    }

    private function targetOf(RequestInterface $request): string
    {
        $uri = $request->getUri();
        $target = $uri->getPath();
        if ('' !== $uri->getQuery()) {
            $target .= '?' . $uri->getQuery();
        }

        return $target;
    }
}
