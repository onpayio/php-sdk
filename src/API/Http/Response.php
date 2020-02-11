<?php

namespace OnPay\API\Http;

class Response {
    /**
     * @var string $statusCode
     */
    protected $statusCode;

    /**
     * @var string $body
     */
    protected $body;

    /**
     * @return string
     */
    public function getStatusCode() {
        return $this->statusCode;
    }

    /**
     * @param string $statusCode
     */
    public function setStatusCode($statusCode) {
        $this->statusCode = $statusCode;
    }

    /**
     * @return string
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody($body) {
        $this->body = $body;
    }
}
