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
        $this->action = isset($data['action']) ? $data['action'] : null;
        $this->author = isset($data['author']) ? $data['author'] : null;
        $this->ip = isset($data['ip']) ? $data['ip'] : null;
        $this->resultText = isset($data['result_text']) ? $data['result_text'] : null;
        $this->resultCode = isset($data['result_code']) ? $data['result_code'] : null;
        $this->successful = isset($data['successful']) ? $data['successful'] : false;

        if(isset($data['date_time'])) {
            $this->date = Converter::toDateTimeFromString($data['date_time']);
        }
    }


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
     * @var bool
     */
    public $successful;

}
