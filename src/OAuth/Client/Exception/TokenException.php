<?php

namespace OnPay\OAuth\Client\Exception;

use Exception;
use OnPay\OAuth\Client\Http\Response;

/**
 * Problem obtaining access_token from token endpoint. This exception also
 * stores the Response object from the Authorization Server token endpoint,
 * to ease debugging.
 */
class TokenException extends OAuthException
{
    /** @var \OnPay\OAuth\Client\Http\Response */
    private $response;

    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, Response $response, $code = 0, Exception $previous = null)
    {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return \OnPay\OAuth\Client\Http\Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
