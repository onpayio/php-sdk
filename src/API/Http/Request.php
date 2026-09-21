<?php

declare(strict_types=1);

namespace OnPay\API\Http;

final class Request {
    /**
     * @var string|null $method
     */
    private ?string $method = null;

    /**
     * @var string|null $uri
     */
    private ?string $uri = null;

    /**
     * @var array $headers
     */
    private array $headers = [];

    /**
     * @var string|null $body
     */
    private ?string $body = '';

    /**
     * @return string|null
     */
    public function getMethod(): ?string {
        return $this->method;
    }

    /**
     * @internal Populated by the SDK; shall not be called outside the library.
     *
     * @param string|null $method
     */
    public function setMethod(?string $method): void {
        $this->method = $method;
    }

    /**
     * @return string|null
     */
    public function getUri(): ?string {
        return $this->uri;
    }

    /**
     * @internal Populated by the SDK; shall not be called outside the library.
     *
     * @param string|null $uri
     */
    public function setUri(?string $uri): void {
        $this->uri = $uri;
    }

    /**
     * @return array
     */
    public function getHeaders(): array {
        return $this->headers;
    }

    /**
     * @internal Populated by the SDK; shall not be called outside the library.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers): void {
        $this->headers = $headers;
    }

    /**
     * @return string|null
     */
    public function getBody(): ?string {
        return $this->body;
    }

    /**
     * @internal Populated by the SDK; shall not be called outside the library.
     *
     * @param string|null $body
     */
    public function setBody(?string $body): void {
        $this->body = $body;
    }
}
