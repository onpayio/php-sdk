<?php

namespace Tests\Unit\Subscription;

use OnPay\API\Subscription\SimpleSubscription;
use OnPay\API\Util\Link;
use PHPUnit\Framework\TestCase;

/**
 * Direct value-object coverage for {@see SimpleSubscription}, pinning both sides of every
 * optional-field read in the constructor.
 *
 * The harness fixtures always carry the identity fields (uuid, subscription_number,
 * status, order_id, currency_code, ...), so their absent side is never taken there. A
 * fully populated payload and an empty payload together prove present AND absent for
 * every field, and the default for the testmode flag.
 */
class SimpleSubscriptionTest extends TestCase
{
    public function testAllFieldsAreReadWhenPresent(): void
    {
        $subscription = new SimpleSubscription([
            '3dsecure' => true,
            'acquirer' => 'clearhaus',
            'card_type' => 'Visa',
            'currency_code' => 208,
            'order_id' => 'order-9001',
            'subscription_number' => 5001,
            'status' => 'active',
            'uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'wallet' => 'mobilepay',
            'testmode' => true,
            'created' => '2026-09-18 09:00:00',
        ]);

        $this->assertTrue($subscription->threeDs);
        $this->assertSame('clearhaus', $subscription->acquirer);
        $this->assertSame('Visa', $subscription->cardType);
        $this->assertSame(208, $subscription->currencyCode);
        $this->assertSame('order-9001', $subscription->orderId);
        $this->assertSame(5001, $subscription->subscriptionNumber);
        $this->assertSame('active', $subscription->status);
        $this->assertSame('f47ac10b-58cc-4372-a567-0e02b2c3d479', $subscription->uuid);
        $this->assertSame('mobilepay', $subscription->wallet);
        $this->assertTrue($subscription->testMode);
        $this->assertInstanceOf(\DateTime::class, $subscription->created);
        $this->assertSame('2026-09-18 09:00:00', $subscription->created->format('Y-m-d H:i:s'));
    }

    public function testEmptyPayloadLeavesEveryFieldAtItsDefault(): void
    {
        $subscription = new SimpleSubscription([]);

        $this->assertNull($subscription->threeDs);
        $this->assertNull($subscription->acquirer);
        $this->assertNull($subscription->cardType);
        $this->assertNull($subscription->currencyCode);
        $this->assertNull($subscription->orderId);
        $this->assertNull($subscription->subscriptionNumber);
        $this->assertNull($subscription->status);
        $this->assertNull($subscription->uuid);
        $this->assertNull($subscription->wallet);
        $this->assertFalse($subscription->testMode);
        $this->assertNull($subscription->created);
        $this->assertNull($subscription->links);
    }

    public function testSetLinksBuildsOneLinkPerRel(): void
    {
        $subscription = new SimpleSubscription([]);
        $subscription->setLinks([
            'self' => '/subscription/abc',
            'transactions' => '/subscription/abc/transactions',
        ]);

        $this->assertCount(2, $subscription->links);
        $this->assertContainsOnlyInstancesOf(Link::class, $subscription->links);
        $this->assertSame('self', $subscription->links[0]->rel);
        $this->assertSame('/subscription/abc', $subscription->links[0]->uri);
        $this->assertSame('transactions', $subscription->links[1]->rel);
        $this->assertSame('/subscription/abc/transactions', $subscription->links[1]->uri);
    }
}
