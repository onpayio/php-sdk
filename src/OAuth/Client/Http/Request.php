<?php

namespace OnPay\OAuth\Client\Http;

class Request {
    /** @var string */
    private $requestMethod;

    /** @var string */
    private $requestUri;

    /** @var string|null */
    private $requestBody;

    /** @var array<string,string> */
    private $requestHeaders;

    /**
     * @param string $requestMethod
     * @param string $requestUri
     * @param string|null $requestBody
     */
    public function __construct($requestMethod, $requestUri, array $requestHeaders = [], $requestBody = null)
    {
        $this->requestMethod = $requestMethod;
        $this->requestUri = $requestUri;
        $this->requestBody = $requestBody;
        $this->requestHeaders = $requestHeaders;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $requestHeaders = [];
        foreach ($this->requestHeaders as $k => $v) {
            // we do NOT want to log HTTP Basic credentials
            if ('Authorization' === $k) {
                if (0 === \strpos($v, 'Basic ')) {
                    $v = 'XXX-REPLACED-FOR-LOG-XXX';
                }
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
     * @param string $requestUri
     *
     * @return Request
     */
    public static function get($requestUri, array $requestHeaders = [])
    {
        return new self('GET', $requestUri, $requestHeaders);
    }

    /**
     * @param string $requestUri
     *
     * @return Request
     */
    public static function post($requestUri, array $postData = [], array $requestHeaders = [])
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

    /**
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    public function setHeader($key, $value)
    {
        $this->requestHeaders[$key] = $value;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->requestMethod;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->requestUri;
    }

    /**
     * @return string|null
     */
    public function getBody()
    {
        return $this->requestBody;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->requestHeaders;
    }
}
