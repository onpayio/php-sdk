<?php
namespace OnPay\API\Subscription;


use OnPay\API\Transaction\SimpleTransaction;

class DetailedSubscription extends SimpleSubscription
{
    /**
     * @var $cardBin
     */
    public $cardBin;

    /**
     * @var $expiryMonth
     */
    public $expiryMonth;

    /**
     * @var $expiryYear
     */
    public $expiryYear;

    /**
     * @var $ip
     */
    public $ip;

    /**
     * @var $history
     */
    public $history;

    /**
     * @var SimpleTransaction[] $transactions
     */
    public $transactions = [];

}
