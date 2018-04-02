<?php
declare(strict_types=1);

namespace OnPay\API\Transaction;


class SimpleTransaction {
    /**
     * @var string
     */
    public $uuid;
    /**
     * @var bool
     */
    public $threeDs;
    /**
     * @var int
     */
    public $amount;
    /**
     * @var string
     */
    public $cardType;
    /**
     * @var int
     */
    public $charged;
    /**
     * @var \DateTime
     */
    public $created;
    /**
     * @var string
     */
    public $currencyCode;
    /**
     * @var string
     */
    public $orderId;
    /**
     * @var int
     */
    public $refunded;
    /**
     * @var string
     */
    public $status;
    /**
     * @var string
     */
    public $transactionNumber;
    /**
     * @var string
     */
    public $wallet;
}
