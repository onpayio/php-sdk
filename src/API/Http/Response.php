<?php

declare(strict_types=1);

namespace OnPay\API\Http;

final class Response {
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
     * @internal Populated by the SDK; shall not be called outside the library.
     *
     * @param int|null $statusCode
     */
    public function setStatusCode(?int $statusCode): void {
        $this->statusCode = $statusCode;
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
