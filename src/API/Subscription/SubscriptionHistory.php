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
        $this->uuid = isset($data['uuid']) ? $data['uuid'] : null;
        $this->action = isset($data['action']) ? $data['action'] : null;
        $this->author = isset($data['author']) ? $data['author'] : null;
        $this->ip = isset($history['ip']) ? $history['ip'] : null;
        $this->resultText = isset($history['result_text']) ? $history['result_text'] : null;
        $this->resultCode = isset($history['result_code']) ? $history['result_code'] : null;
        $this->subscriptionNumber = isset($data['subscription_number']) ? $data['subscription_number'] :  null;
        $this->subscriptionUuid = isset($data['subscription_uuid']) ? $data['subscription_uuid'] : null;

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
