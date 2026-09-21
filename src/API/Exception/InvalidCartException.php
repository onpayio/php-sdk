<?php

declare(strict_types=1);

namespace OnPay\API\Exception;

class InvalidCartException extends OnPayException {
    /**
     * Contains all the errors
     *
     * @var string[]
     */
    public array $errors = [];

    /**
     * @param string[] $errors
     */
    public function __construct(array $errors) {
        $this->errors = $errors;
        parent::__construct(count($errors) . ' validation errors: ' . implode(', ', $errors));
    }
}
