<?php

namespace OnPay\API\Http;

class Request {
    /**
     * @var string|null $method
     */
    protected ?string $method = null;

    /**
     * @var string|null $uri
     */
    protected ?string $uri = null;

    /**
     * @var array $headers
     */
    protected array $headers = [];

    /**
     * @var string|null $body
     */
    protected ?string $body = '';

    /**
     * @return string|null
     */
    public function getMethod(): ?string {
        return $this->method;
    }

    /**
     * @param string|null $method
     */
    public function setMethod($method): void {
        $this->method = $method;
    }

    /**
     * @return string|null
     */
    public function getUri(): ?string {
        return $this->uri;
    }

    /**
     * @param string|null $uri
     */
    public function setUri($uri): void {
        $this->uri = $uri;
    }

    /**
     * @return array
     */
    public function getHeaders(): array {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders($headers): void {
        $this->headers = $headers;
    }

    /**
     * @return string|null
     */
    public function getBody(): ?string {
        return $this->body;
    }

    /**
     * @param string|null $body
     */
    public function setBody($body): void {
        $this->body = $body;
    }
}
