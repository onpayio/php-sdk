<?php

namespace OnPay\OAuth\Client\Http;

use OnPay\OAuth\Client\Http\Exception\ResponseException;
use OnPay\OAuth\Client\Json;

class Response
{
    /** @var int */
    private $statusCode;

    /** @var string */
    private $responseBody;

    /** @var array <string,string> */
    private $responseHeaders;

    /**
     * @param int                  $statusCode
     * @param string               $responseBody
     * @param array<string,string> $responseHeaders
     */
    public function __construct($statusCode, $responseBody, array $responseHeaders = [])
    {
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
        $this->responseHeaders = $responseHeaders;
    }

    /**
     * @return string
     */
    public function __toString()
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

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->responseBody;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasHeader($key)
    {
        foreach (\array_keys($this->responseHeaders) as $k) {
            if (\strtoupper($key) === \strtoupper($k)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getHeader($key)
    {
        foreach ($this->responseHeaders as $k => $v) {
            if (\strtoupper($key) === \strtoupper($k)) {
                return $v;
            }
        }

        throw new ResponseException(\sprintf('header "%s" not set', $key));
    }

    /**
     * @return mixed
     */
    public function json()
    {
        if (false === \strpos($this->getHeader('Content-Type'), 'application/json')) {
            throw new ResponseException('response MUST have JSON content type');
        }

        return Json::decode($this->responseBody);
    }

    /**
     * @return bool
     */
    public function isOkay()
    {
        return 200 <= $this->statusCode && 300 > $this->statusCode;
    }
}
