<?php

namespace OnPay\API\Transaction;


use OnPay\API\Util\Converter;
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
        $this->uuid = DataReader::stringOrNull($data, 'uuid');
        $this->threeDs = DataReader::boolOrNull($data, '3dsecure');
        $this->acquirer = DataReader::stringOrNull($data, 'acquirer');
        $this->amount = DataReader::intOrNull($data, 'amount');
        $this->cardType = DataReader::stringOrNull($data, 'card_type');
        $this->charged = DataReader::intOrNull($data, 'charged');
        $created = DataReader::stringOrNull($data, 'created');
        if (null !== $created) {
            $createdDateTime = Converter::toDateTimeFromString($created);
            if (false !== $createdDateTime) {
                $this->created = $createdDateTime;
            }
        }
        $this->currencyCode = DataReader::intOrNull($data, 'currency_code');
        $this->orderId = DataReader::stringOrNull($data, 'order_id');
        $this->refunded = DataReader::intOrNull($data, 'refunded');
        $this->status = DataReader::stringOrNull($data, 'status');
        $this->transactionNumber = DataReader::intOrNull($data, 'transaction_number');
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

    public ?int $amount = null;

    public ?string $acquirer = null;

    public ?string $cardType = null;

    public ?int $charged = null;

    public ?\DateTime $created = null;

    public ?int $currencyCode = null;

    public ?string $orderId = null;

    public ?int $refunded = null;

    public ?string $status = null;

    public ?bool $threeDs = null;

    public ?int $transactionNumber = null;

    public ?string $uuid = null;

    public ?string $wallet = null;

    public bool $hasCardholderData = false;

    public bool $testMode = false;

    /**
     * @var Link[]|null
     */
    public ?array $links = null;
}
