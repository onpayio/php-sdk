<?php

namespace OnPay\API\Payment;

use OnPay\API\Util\DataReader;

class SimplePayment {

    private ?string $uuid;
    private ?int $amount;
    private ?string $currency;
    private ?string $expiration;
    private ?string $language;
    private ?string $method;
    private ?string $paymentLink;

    /**
     * @param array $response
     */
    public function __construct(array $response) {
        $data = DataReader::arrayOr($response, 'data');
        $links = DataReader::arrayOr($response, 'links');

        $this->uuid = DataReader::stringOrNull($data, 'payment_uuid');
        $this->amount = DataReader::intOrNull($data, 'amount');
        $this->currency = DataReader::stringOrNull($data, 'currency_code');
        $this->expiration = DataReader::stringOrNull($data, 'expiration');
        $this->language = DataReader::stringOrNull($data, 'language');
        $this->method = DataReader::stringOrNull($data, 'method');
        $this->paymentLink = DataReader::stringOrNull($links, 'payment_window');
    }

    public function getUuid(): ?string {
        return $this->uuid;
    }

    public function getAmount(): ?int {
        return $this->amount;
    }

    public function getCurrency(): ?string {
        return $this->currency;
    }

    public function getExpiration(): ?string {
        return $this->expiration;
    }

    public function getLanguage(): ?string {
        return $this->language;
    }

    public function getMethod(): ?string {
        return $this->method;
    }

    public function getPaymentWindowLink(): ?string {
        return $this->paymentLink;
    }

}
