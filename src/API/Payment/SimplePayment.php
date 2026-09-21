<?php

declare(strict_types=1);

namespace OnPay\API\Payment;

use OnPay\API\Util\DataReader;

final class SimplePayment {

    private string $uuid;
    private ?int $amount;
    private string $currency;
    private ?string $expiration;
    private ?string $language;
    private ?string $method;
    private string $paymentLink;

    /**
     * @internal Shall not be used outside the library
     *
     * @param array $response
     *
     * @throws \OnPay\API\Exception\ApiException
     */
    public function __construct(array $response) {
        $data = DataReader::arrayOr($response, 'data');
        $links = DataReader::arrayOr($response, 'links');

        $this->uuid = DataReader::requireString($data, 'payment_uuid');
        $this->amount = DataReader::intOrNull($data, 'amount');
        $this->currency = DataReader::requireString($data, 'currency_code');
        $this->expiration = DataReader::stringOrNull($data, 'expiration');
        $this->language = DataReader::stringOrNull($data, 'language');
        $this->method = DataReader::stringOrNull($data, 'method');
        $this->paymentLink = DataReader::requireString($links, 'payment_window');
    }

    public function getUuid(): string {
        return $this->uuid;
    }

    public function getAmount(): ?int {
        return $this->amount;
    }

    public function getCurrency(): string {
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

    public function getPaymentWindowLink(): string {
        return $this->paymentLink;
    }

}
