<?php

namespace OnPay\API\Subscription;

use OnPay\API\Util\Converter;

class SubscriptionHistory
{
    /**
     * @internal Shall not be used outside the library
     * SubscriptionHistory constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->uuid = $data['uuid'] ?? null;
        $this->action = $data['action'] ?? null;
        $this->author = $data['author'] ?? null;
        $this->ip = $history['ip'] ?? null;
        $this->resultText = $history['result_text'] ?? null;
        $this->resultCode = $history['result_code'] ?? null;
        $this->subscriptionNumber = $data['subscription_number'] ?? null;
        $this->subscriptionUuid = $data['subscription_uuid'] ?? null;

        if(isset($data['date_time'])) {
            $this->date = Converter::toDateTimeFromString($data['date_time']);
        }
    }

    /** @var string  */
    public $uuid;

    /** @var $action */
    public $action;

    /** @var $author */
    public $author;

    /** @var $date */
    public $date;

    /** @var $ip */
    public $ip;

    /** @var $resultCode */
    public $resultCode;

    /** @var $resultText */
    public $resultText;

    /** @var $subscriptionNumber */
    public $subscriptionNumber;

    /** @var $subscriptionUuid */
    public $subscriptionUuid;

}