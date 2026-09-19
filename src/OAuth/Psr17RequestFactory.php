<?php

declare(strict_types=1);

namespace OnPay\OAuth;

use League\OAuth2\Client\Tool\RequestFactory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Lets league/oauth2-client 2.x build its token endpoint requests through the PSR-17
 * factories configured on OnPayAPI instead of hard-wiring guzzlehttp/psr7, so the
 * OAuth and API traffic share one message implementation.
 *
 * Shim for league 2.x only: 3.x takes RequestFactoryInterface/StreamFactoryInterface
 * collaborators directly, at which point this class goes away.
 *
 * @internal Shall not be used outside the library.
 */
class Psr17RequestFactory extends RequestFactory {
    private RequestFactoryInterface $requestFactory;

    private StreamFactoryInterface $streamFactory;

    public function __construct(RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory) {
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * @param null|string $method
     * @param null|string|\Psr\Http\Message\UriInterface $uri
     * @param array<array-key,mixed> $headers
     * @param mixed $body
     * @param string $version
     *
     * @return RequestInterface
     *
     * @psalm-suppress LessSpecificImplementedReturnType the parent documents guzzlehttp/psr7's Request, league only relies on RequestInterface
     */
    public function getRequest($method, $uri, array $headers = [], $body = null, $version = '1.1') {
        $request = $this->requestFactory->createRequest((string) $method, (string) $uri)->withProtocolVersion($version);
        /** @psalm-suppress MixedAssignment,MixedArgument header values are strings or lists of strings, as PSR-7 requires */
        foreach ($headers as $name => $value) {
            $request = $request->withHeader((string) $name, $value);
        }

        if ($body instanceof StreamInterface) {
            $request = $request->withBody($body);
        } elseif (\is_string($body)) {
            $request = $request->withBody($this->streamFactory->createStream($body));
        } elseif (\is_resource($body)) {
            $request = $request->withBody($this->streamFactory->createStreamFromResource($body));
        }

        return $request;
    }
}
