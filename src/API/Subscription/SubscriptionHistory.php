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

    /**
     * @var string
     */
    public $uuid;

    /**
     * @var string
     */
    public $action;

    /**
     * @var string
     */
    public $author;

    /**
     * @var \DateTime
     */
    public $date;

    /**
     * @var string
     */
    public $ip;

    /**
     * @var string
     */
    public $resultCode;

    /**
     * @var string
     */
    public $resultText;

    /**
     * @var string
     */
    public $subscriptionNumber;

    /**
     * @var string
     */
    public $subscriptionUuid;

}
