<?php
/**
 * Created by PhpStorm.
 * User: mmu
 * Date: 17/04/2018
 * Time: 11.08
 */

namespace OnPay\API\Subscription;


class SimpleSubscription
{
    /**
     * @var $uuid
     */
    public $uuid;

    /**
     * @var $threeDs
     */
    public $threeDs;

    /**
     * @var $archived
     */
    public $archived;

    /**
     * @var $cardType
     */
    public $cardType;

    /**
     * @var $created
     */
    public $created;

    /**
     * @var $currencyCode
     */
    public $currencyCode;

    /**
     * @var $orderId
     */
    public $orderId;

    /**
     * @var $status
     */
    public $status;

    /**
     * @var $subscriptionNumber
     */
    public $subscriptionNumber;

    /**
     * @var $wallet
     */
    public $wallet;

    /**
     * @var $links
     */
    public $links;
}