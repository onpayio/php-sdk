<?php

namespace OnPay\API\Payment;

class SimplePayment {

    private $uuid;
    private $amount;
    private $currency;
    private $expiration;
    private $language;
    private $method;
    private $paymentLink;

    public function __construct($response) {
        $this->uuid = $response['data']['payment_uuid'];
        $this->amount = $response['data']['amount'];
        $this->currency = $response['data']['currency_code'];
        $this->expiration = $response['data']['expiration'];
        $this->language = $response['data']['language'];
        $this->method = $response['data']['method'];
        $this->paymentLink = $response['links']['payment_window'];
    }

    public function getUuid() {
        return $this->uuid;
    }

    public function getAmount() {
        return $this->amount;
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function getExpiration() {
        return $this->expiration;
    }

    public function getLanguage() {
        return $this->language;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getPaymentWindowLink() {
        return $this->paymentLink;
    }

}
