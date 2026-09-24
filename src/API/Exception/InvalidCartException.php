<?php

declare(strict_types=1);

namespace OnPay\API\Exception;

final class InvalidCartException extends OnPayException {
    /**
     * Contains all the errors
     *
     * @var string[]
     */
    public array $errors = [];

    /**
     * @internal Raised by the SDK; shall not be constructed outside the library.
     *
     * @param string[] $errors
     */
    public function __construct(array $errors) {
        $this->errors = $errors;
        parent::__construct(count($errors) . ' validation errors: ' . implode(', ', $errors));
    }
}
