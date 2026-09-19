<?php

declare(strict_types=1);

namespace OnPay\OAuth\Client\Http;

class Request {
    private string $requestMethod;

    private string $requestUri;

    private ?string $requestBody;

    /** @var array<string,string> */
    private array $requestHeaders;

    /**
     * @param array<string,string> $requestHeaders
     */
    public function __construct(string $requestMethod, string $requestUri, array $requestHeaders = [], ?string $requestBody = null)
    {
        $this->requestMethod = $requestMethod;
        $this->requestUri = $requestUri;
        $this->requestBody = $requestBody;
        $this->requestHeaders = $requestHeaders;
    }

    public function __toString(): string
    {
        $requestHeaders = [];
        foreach ($this->requestHeaders as $k => $v) {
            // we do NOT want to log credentials of any scheme (Basic, Bearer, ...)
            if ('Authorization' === $k) {
                $v = 'XXX-REPLACED-FOR-LOG-XXX';
            }
            $requestHeaders[] = \sprintf('%s: %s', $k, $v);
        }

        $requestBody = null === $this->requestBody ? '' : $this->requestBody;

        return \sprintf(
            '[requestMethod=%s, requestUri=%s, requestHeaders=[%s], requestBody=%s]',
            $this->requestMethod,
            $this->requestUri,
            \implode(', ', $requestHeaders),
            $requestBody
        );
    }

    /**
     * @param array<string, string|null> $queryParameters
     * @param array<string,string> $requestHeaders
     */
    public static function get(string $requestUri, array $queryParameters = [], array $requestHeaders = []): self
    {
        if (0 !== \count($queryParameters)) {
            $qP = \http_build_query($queryParameters, '', '&', PHP_QUERY_RFC3986);
            $requestUri .= false === \strpos($requestUri, '?') ? '?'.$qP : '&'.$qP;
        }

        return new self('GET', $requestUri, $requestHeaders);
    }

    /**
     * @param array<string, string|null> $postData
     * @param array<string,string> $requestHeaders
     */
    public static function post(string $requestUri, array $postData = [], array $requestHeaders = []): self
    {
        return new self(
            'POST',
            $requestUri,
            \array_merge(
                $requestHeaders,
                ['Content-Type' => 'application/x-www-form-urlencoded']
            ),
            \http_build_query($postData, '&')
        );
    }

    public function setHeader(string $key, string $value): void
    {
        $this->requestHeaders[$key] = $value;
    }

    public function getMethod(): string
    {
        return $this->requestMethod;
    }

    public function getUri(): string
    {
        return $this->requestUri;
    }

    public function getBody(): ?string
    {
        return $this->requestBody;
    }

    /**
     * @return array<string,string>
     */
    public function getHeaders(): array
    {
        return $this->requestHeaders;
    }
}
