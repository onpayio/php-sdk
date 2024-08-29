<?php

namespace OnPay\OAuth\Client\Exception;

use Exception;

/**
 * Problem obtaining authorization from authorize endpoint.
 */
class AuthorizeException extends OAuthException {
    /** @var string|null */
    private $description;

    /**
     * @param string      $message
     * @param string|null $description
     * @param int         $code
     */
    public function __construct($message, $description, $code = 0, Exception $previous = null)
    {
        $this->description = $description;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }
}
