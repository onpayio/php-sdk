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
        $this->action = isset($data['action']) ? $data['action'] : null;
        $this->amount = isset($data['amount']) ? $data['amount'] : null;
        $this->author = isset($data['author']) ? $data['author'] : null;
        $this->ip = isset($data['ip']) ? $data['ip'] :  null;
        $this->resultCode = isset($data['result_code']) ? $data['result_code'] : null;
        $this->resultText = isset($data['result_text']) ? $data['result_text'] : null;
        $this->successful = isset($data['successful']) ? $data['successful'] : false;

        if(isset($data['date_time'])) {
            $this->dateTime = Converter::toDateTimeFromString($data['date_time']);
        }
    }

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

    /**
     * @var bool
     */
    public $successful;
}

