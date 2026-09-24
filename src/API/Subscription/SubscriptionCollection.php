<?php

declare(strict_types=1);

namespace OnPay\API\Subscription;
use OnPay\API\Util\Pagination;
final class SubscriptionCollection
{
    /**
     * @var SimpleSubscription[]
     */
    public array $subscriptions = [];

    public ?Pagination $pagination = null;
}
