<?php
namespace OnPay\API\Subscription;


use OnPay\API\Util\Link;

class SimpleSubscription
{
    /**
     * @var string
     */
    public $uuid;

    /**
     * @var string
     */
    public $threeDs;

    /**
     * @var string
     */
    public $archived;

    /**
     * @var string
     */
    public $cardType;

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
     * @var string
     */
    public $status;

    /**
     * @var string
     */
    public $subscriptionNumber;

    /**
     * @var string
     */
    public $wallet;

    /**
     * @var Link[]
     */
    public $links;
}
