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
        $this->threeDs = isset($data['3dsecure']) ? $data['3dsecure'] : null;
        $this->acquirer = isset($data['acquirer']) ? $data['acquirer'] :  null;
        $this->cardType = isset($data['card_type']) ? $data['card_type'] : null;
        $this->currencyCode = isset($data['currency_code']) ? $data['currency_code'] : null;
        $this->orderId = isset($data['order_id']) ? $data['order_id'] : null;
        $this->subscriptionNumber = isset($data['subscription_number']) ? $data['subscription_number'] : null;
        $this->status = isset($data['status']) ? $data['status'] : null;
        $this->uuid = isset($data['uuid']) ? $data['uuid'] : null;
        $this->wallet = isset($data['wallet']) ? $data['wallet'] : null;

        if(isset($data['created'])) {
            $this->created = Converter::toDateTimeFromString($data['created']);
        }
    }

    /**
     * @internal Shall not be used outside the library
     * @param $links
     */
    public function setLinks(array $links) {
        foreach ($links as $rel => $link) {
            $linkItem = new Link($rel, $link);
            $this->links[] = $linkItem;
        }
    }


    /**
     * @var string
     */
    public $acquirer;

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
     * @var bool
     */
    public $threeDs;

    /**
     * @var string
     */
    public $uuid;

    /**
     * @var string
     */
    public $wallet;

    /**
     * @var Link[]
     */
    public $links;
}
