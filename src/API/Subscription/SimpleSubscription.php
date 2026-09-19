<?php
namespace OnPay\API\Subscription;


use OnPay\API\Util\Converter;
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
        $this->threeDs = DataReader::boolOrNull($data, '3dsecure');
        $this->acquirer = DataReader::stringOrNull($data, 'acquirer');
        $this->cardType = DataReader::stringOrNull($data, 'card_type');
        $this->currencyCode = DataReader::intOrNull($data, 'currency_code');
        $this->orderId = DataReader::stringOrNull($data, 'order_id');
        $this->subscriptionNumber = DataReader::intOrNull($data, 'subscription_number');
        $this->status = DataReader::stringOrNull($data, 'status');
        $this->uuid = DataReader::stringOrNull($data, 'uuid');
        $this->wallet = DataReader::stringOrNull($data, 'wallet');
        $this->testMode = DataReader::boolOr($data, 'testmode', false);

        $created = DataReader::stringOrNull($data, 'created');
        if ($created !== null) {
            $createdDateTime = Converter::toDateTimeFromString($created);
            if ($createdDateTime !== false) {
                $this->created = $createdDateTime;
            }
        }
    }

    /**
     * @internal Shall not be used outside the library
     * @param array<array-key, mixed> $links
     */
    public function setLinks(array $links): void {
        $result = [];
        foreach (array_keys($links) as $rel) {
            $result[] = new Link($rel, $links[$rel]);
        }
        $this->links = $result;
    }


    /**
     * @var ?string
     */
    public $acquirer;

    /**
     * @var ?string
     */
    public $cardType;

    /**
     * @var ?\DateTime
     */
    public $created = null;

    /**
     * @var ?int
     */
    public $currencyCode;

    /**
     * @var ?string
     */
    public $orderId;

    /**
     * @var ?string
     */
    public $status;

    /**
     * @var ?int
     */
    public $subscriptionNumber;

    /**
     * @var ?bool
     */
    public $threeDs;

    /**
     * @var ?string
     */
    public $uuid;

    /**
     * @var ?string
     */
    public $wallet;

    /**
     * @var bool
     */
    public $testMode = false;

    /**
     * @var Link[]|null
     */
    public ?array $links = null;
}
