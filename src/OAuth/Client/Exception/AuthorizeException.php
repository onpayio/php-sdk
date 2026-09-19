<?php

declare(strict_types=1);

namespace OnPay\OAuth\Client\Exception;

use Exception;

/**
 * Problem obtaining authorization from authorize endpoint.
 */
class AuthorizeException extends OAuthException {
    private ?string $description = null;

    public function __construct(string $message, ?string $description, int $code = 0, ?Exception $previous = null)
    {
        $this->description = $description;
        parent::__construct($message, $code, $previous);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
