<?php


namespace OnPay\API\Transaction;


use OnPay\API\Util\Converter;

class TransactionHistory {

    /**
     * @internal Shall not be used outside the library
     * TransactionHistory constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->uuid = isset($data['uuid']) ? $data['uuid'] : null;
        $this->action = isset($data['action']) ? $data['action'] : null;
        $this->amount = isset($data['amount']) ? $data['amount'] : null;
        $this->author = isset($data['author']) ? $data['author'] : null;
        $this->ip = isset($history['ip']) ? $history['ip'] :  null;
        $this->resultText = (isset($history['result_text'])) ? $history['result_text'] : null;
        $this->resultCode = (isset($history['result_code'])) ? $history['result_code'] : null;

        if(isset($data['date_time'])) {
            $this->dateTime = Converter::toDateTimeFromString($data['date_time']);
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
     * @var int
     */
    public $amount;

    /**
     * @var string
     */
    public $author;

    /**
     * @var \DateTime
     */
    public $dateTime;

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
}

