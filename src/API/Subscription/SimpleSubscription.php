<?php
namespace OnPay\API\Subscription;


use OnPay\API\Util\DataReader;
use OnPay\API\Util\Link;

class SimpleSubscription
{
    /**
     * @internal Shall not be used outside the library
     * SimpleSubscription constructor.
     * @param array<array-key, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->threeDs = DataReader::requireBool($data, '3dsecure');
        $this->acquirer = DataReader::stringOrNull($data, 'acquirer');
        $this->cardType = DataReader::stringOrNull($data, 'card_type');
        $this->currencyCode = DataReader::requireInt($data, 'currency_code');
        $this->orderId = DataReader::stringOrNull($data, 'order_id');
        $this->subscriptionNumber = DataReader::requireInt($data, 'subscription_number');
        $this->status = DataReader::requireString($data, 'status');
        $this->uuid = DataReader::requireString($data, 'uuid');
        $this->wallet = DataReader::stringOrNull($data, 'wallet');
        $this->testMode = DataReader::boolOr($data, 'testmode', false);

        $this->created = DataReader::requireDateTime($data, 'created');
    }

    /**
     * @internal Shall not be used outside the library
     * @param array<array-key, mixed> $links
     */
    public function setLinks(array $links): void {
        $result = [];
        foreach (array_keys($links) as $rel) {
            $result[] = new Link((string) $rel, DataReader::stringOrNull($links, (string) $rel));
        }
        $this->links = $result;
    }


    public ?string $acquirer = null;

    public ?string $cardType = null;

    public \DateTime $created;

    public int $currencyCode;

    public ?string $orderId = null;

    public string $status;

    public int $subscriptionNumber;

    public bool $threeDs;

    public string $uuid;

    public ?string $wallet = null;

    public bool $testMode = false;

    /**
     * @var Link[]|null
     */
    public ?array $links = null;
}
