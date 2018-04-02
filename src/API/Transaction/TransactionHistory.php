<?php


namespace OnPay\API\Transaction;


class TransactionHistory {
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
