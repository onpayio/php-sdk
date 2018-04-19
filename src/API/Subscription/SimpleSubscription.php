<?php
namespace OnPay\API\Subscription;


use OnPay\API\Util\Converter;
use OnPay\API\Util\Link;

class SimpleSubscription
{
    /**
     * @internal Shall not be used outside the library
     * SimpleSubscription constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->threeDs = $data['3dsecure'] ?? null;
        $this->cardType = $data['card_type'] ?? null;
        $this->currencyCode = $data['currency_code'] ?? null;
        $this->orderId = $data['order_id'] ?? null;
        $this->subscriptionNumber = $data['subscription_number'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->uuid = $data['uuid'] ?? null;
        $this->wallet = $data['wallet'] ?? null;
        $this->created = Converter::toDateTimeFromString($data['created']) ?? null;

        foreach ($data['links'] as $link) {
            $linkItem = new Link($link);
            $this->links[] = $linkItem;
        }
    }

    /**
     * @var string
     */
    public $uuid;

    /**
     * @var string
     */
    public $threeDs;

    /**
     * @var string
     */
    public $archived;

    /**
     * @var string
     */
    public $cardType;

    /**
     * @var \DateTime
     */
    public $created;

    /**
     * @var string
     */
    public $currencyCode;

    /**
     * @var string
     */
    public $orderId;

    /**
     * @var string
     */
    public $status;

    /**
     * @var string
     */
    public $subscriptionNumber;

    /**
     * @var string
     */
    public $wallet;

    /**
     * @var Link[]
     */
    public $links;
}
