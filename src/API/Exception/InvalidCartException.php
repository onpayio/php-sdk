<?php

namespace OnPay\API\Exception;

class InvalidCartException extends \Exception {
    /**
     * Contains all the errors
     *
     * @var string[]
     */
    public $errors;
    public function __construct(array $errors) {
        $this->errors = $errors;
        parent::__construct(count($errors) . ' validation errors: ' . implode(', ', $errors));
    }
}
