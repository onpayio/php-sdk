<?php

declare(strict_types=1);

namespace OnPay\API\Transaction;


use OnPay\API\Util\DataReader;
use OnPay\API\Util\Link;

class SimpleTransaction {

    /**
     * @internal Shall not be used outside the library
     * SimpleTransaction constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->uuid = DataReader::requireString($data, 'uuid');
        $this->threeDs = DataReader::requireBool($data, '3dsecure');
        $this->acquirer = DataReader::stringOrNull($data, 'acquirer');
        $this->amount = DataReader::requireInt($data, 'amount');
        $this->cardType = DataReader::stringOrNull($data, 'card_type');
        $this->charged = DataReader::requireInt($data, 'charged');
        $this->created = DataReader::requireDateTime($data, 'created');
        $this->currencyCode = DataReader::requireInt($data, 'currency_code');
        $this->orderId = DataReader::stringOrNull($data, 'order_id');
        $this->refunded = DataReader::requireInt($data, 'refunded');
        $this->status = DataReader::requireString($data, 'status');
        $this->transactionNumber = DataReader::requireInt($data, 'transaction_number');
        $this->wallet = DataReader::stringOrNull($data, 'wallet');
        $this->hasCardholderData = DataReader::boolOr($data, 'has_cardholder_data', false);
        $this->testMode = DataReader::boolOr($data, 'testmode', false);
    }

    /**
     * @internal Shall not be used outside the library
     * @param array $links
     */
    public function setLinks(array $links): void {
        $result = [];
        foreach (array_keys($links) as $rel) {
            $result[] = new Link((string) $rel, DataReader::stringOrNull($links, (string) $rel));
        }
        $this->links = $result;
    }

    public int $amount;

    public ?string $acquirer = null;

    public ?string $cardType = null;

    public int $charged;

    public \DateTime $created;

    public int $currencyCode;

    public ?string $orderId = null;

    public int $refunded;

    public string $status;

    public bool $threeDs;

    public int $transactionNumber;

    public string $uuid;

    public ?string $wallet = null;

    /**
     * True does not guarantee the cardholder data is present; check the object itself.
     */
    public bool $hasCardholderData = false;

    public bool $testMode = false;

    /**
     * @var Link[]|null
     */
    public ?array $links = null;
}
