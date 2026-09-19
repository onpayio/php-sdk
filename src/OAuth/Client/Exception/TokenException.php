<?php

declare(strict_types=1);

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
    private Response $response;

    public function __construct(string $message, Response $response, int $code = 0, ?Exception $previous = null)
    {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
