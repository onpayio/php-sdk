<?php
namespace OnPay\API\Subscription;


use OnPay\API\Transaction\SimpleTransaction;
use OnPay\API\Transaction\TransactionHistory;

class DetailedSubscription extends SimpleSubscription
{
    /**
     * @var string
     */
    public $cardBin;

    /**
     * @var string
     */
    public $expiryMonth;

    /**
     * @var string
     */
    public $expiryYear;

    /**
     * @var string
     */
    public $ip;

    /**
     * @var TransactionHistory[]
     */
    public $history = [];

    /**
     * @var SimpleTransaction[]
     */
    public $transactions = [];

}
