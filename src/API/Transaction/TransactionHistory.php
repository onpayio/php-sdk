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
        $this->uuid = $data['uuid'] ?? null;
        $this->action = $data['action'] ?? null;
        $this->amount = $data['amount'] ?? null;
        $this->author = $data['author'] ?? null;
        $this->ip = $history['ip'] ?? null;
        $this->resultText = $history['result_text'] ?? null;
        $this->resultCode = $history['result_code'] ?? null;

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

