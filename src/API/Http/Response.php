<?php

namespace OnPay\API\Http;

class Response {
    /**
     * @var int|null $statusCode
     */
    protected ?int $statusCode = null;

    /**
     * @var string|null $body
     */
    protected ?string $body = null;

    /**
     * @return int|null
     */
    public function getStatusCode(): ?int {
        return $this->statusCode;
    }

    /**
     * @param int|null $statusCode
     */
    public function setStatusCode($statusCode): void {
        $this->statusCode = $statusCode;
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
