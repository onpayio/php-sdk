<?php

declare(strict_types=1);

namespace OnPay\API\Subscription;
use OnPay\API\Util\Pagination;
class SubscriptionCollection
{
    /**
     * @var SimpleSubscription[]
     */
    public array $subscriptions = [];

    public ?Pagination $pagination = null;
}
