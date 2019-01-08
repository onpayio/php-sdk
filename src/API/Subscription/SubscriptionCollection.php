<?php
namespace OnPay\API\Subscription;
use OnPay\API\Util\Pagination;
class SubscriptionCollection
{
    /**
     * @var SimpleSubscription[]
     */
    public $subscriptions = [];
    /**
     * @var Pagination
     */
    public $pagination;
}

