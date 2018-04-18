<?php
declare(strict_types=1);

namespace OnPay\API\Transaction;


class DetailedTransaction extends SimpleTransaction {
    /**
     * @var string
     */
    public $acquirer;
    /**
     * @var string
     */
    public $cardBin;
    /**
     * @var int
     */
    public $expiryMonth;
    /**
     * @var int
     */
    public $expiryYear;
    /**
     * @var string
     */
    public $ip;
    /**
     * @var string
     */
    public $subscriptionUuid;
    /**
     * @var TransactionHistory[]
     */
    public $history = [];

    public $subscriptionNumber;


}
