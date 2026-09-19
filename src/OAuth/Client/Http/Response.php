<?php

declare(strict_types=1);

namespace OnPay\OAuth\Client\Http;

use OnPay\OAuth\Client\Http\Exception\ResponseException;
use OnPay\OAuth\Client\Json;

class Response
{
    private int $statusCode;

    private string $responseBody;

    /** @var array<string,string> */
    private array $responseHeaders;

    /**
     * @param array<string,string> $responseHeaders
     */
    public function __construct(int $statusCode, string $responseBody, array $responseHeaders = [])
    {
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
        $this->responseHeaders = $responseHeaders;
    }

    public function __toString(): string
    {
        $responseHeaders = [];
        foreach ($this->responseHeaders as $k => $v) {
            $responseHeaders[] = \sprintf('%s: %s', $k, $v);
        }

        return \sprintf(
            '[statusCode=%d, responseHeaders=[%s], responseBody=%s]',
            $this->statusCode,
            \implode(', ', $responseHeaders),
            $this->responseBody
        );
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): string
    {
        return $this->responseBody;
    }

    /**
     * @return array<string,string>
     */
    public function getHeaders(): array
    {
        return $this->responseHeaders;
    }

    public function hasHeader(string $key): bool
    {
        foreach (\array_keys($this->responseHeaders) as $k) {
            if (\strtoupper($key) === \strtoupper($k)) {
                return true;
            }
        }

        return false;
    }

    public function getHeader(string $key): string
    {
        foreach ($this->responseHeaders as $k => $v) {
            if (\strtoupper($key) === \strtoupper($k)) {
                return $v;
            }
        }

        throw new ResponseException(\sprintf('header "%s" not set', $key));
    }

    public function json(): mixed
    {
        if (false === \strpos($this->getHeader('Content-Type'), 'application/json')) {
            throw new ResponseException('response MUST have JSON content type');
        }

        return Json::decode($this->responseBody);
    }

    public function isOkay(): bool
    {
        return 200 <= $this->statusCode && 300 > $this->statusCode;
    }
}
