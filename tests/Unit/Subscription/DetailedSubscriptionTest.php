<?php

namespace Tests\Unit\Subscription;

use OnPay\API\Subscription\DetailedSubscription;
use OnPay\API\Subscription\SubscriptionHistory;
use OnPay\API\Transaction\SimpleTransaction;
use PHPUnit\Framework\TestCase;

/**
 * Direct value-object coverage for {@see DetailedSubscription}, pinning both sides of every
 * optional-field read the constructor adds on top of {@see \OnPay\API\Subscription\SimpleSubscription}.
 *
 * The detailed fixture always carries the card/ip detail fields (expiry, card_*, ip*) and
 * never carries `fee`, so each read only ever takes one side there. A minimal payload
 * (only the mandatory `history` and `transactions` lists) proves the null defaults; a
 * fully populated payload proves the reads.
 */
class DetailedSubscriptionTest extends TestCase
{
    public function testAllDetailFieldsAreReadWhenPresent(): void
    {
        $subscription = new DetailedSubscription([
            'uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'expiry_month' => 11,
            'expiry_year' => 2029,
            'card_country' => '208',
            'card_bin' => '457173',
            'ip' => '203.0.113.11',
            'ip_country' => '208',
            'fee' => 95,
            'history' => [
                ['action' => 'created', 'successful' => true],
                ['action' => 'authorize', 'successful' => true],
            ],
            'transactions' => [
                ['uuid' => 'tx-1', 'amount' => 12500],
                ['uuid' => 'tx-2', 'amount' => 500],
            ],
        ]);

        // Parent constructor still runs first.
        $this->assertSame('f47ac10b-58cc-4372-a567-0e02b2c3d479', $subscription->uuid);

        $this->assertSame(11, $subscription->expiryMonth);
        $this->assertSame(2029, $subscription->expiryYear);
        $this->assertSame('208', $subscription->cardCountry);
        $this->assertSame('457173', $subscription->cardBin);
        $this->assertSame('203.0.113.11', $subscription->ip);
        $this->assertSame('208', $subscription->ipCountry);
        $this->assertSame(95, $subscription->fee);

        $this->assertCount(2, $subscription->history);
        $this->assertContainsOnlyInstancesOf(SubscriptionHistory::class, $subscription->history);
        $this->assertSame('created', $subscription->history[0]->action);
        $this->assertSame('authorize', $subscription->history[1]->action);

        $this->assertCount(2, $subscription->transactions);
        $this->assertContainsOnlyInstancesOf(SimpleTransaction::class, $subscription->transactions);
        $this->assertSame('tx-1', $subscription->transactions[0]->uuid);
        $this->assertSame(12500, $subscription->transactions[0]->amount);
        $this->assertSame('tx-2', $subscription->transactions[1]->uuid);
        $this->assertSame(500, $subscription->transactions[1]->amount);
    }

    public function testMinimalPayloadLeavesEveryDetailFieldAtItsDefault(): void
    {
        $subscription = new DetailedSubscription(['history' => [], 'transactions' => []]);

        $this->assertNull($subscription->expiryMonth);
        $this->assertNull($subscription->expiryYear);
        $this->assertNull($subscription->cardCountry);
        $this->assertNull($subscription->cardBin);
        $this->assertNull($subscription->ip);
        $this->assertNull($subscription->ipCountry);
        $this->assertNull($subscription->fee);
        $this->assertSame([], $subscription->history);
        $this->assertSame([], $subscription->transactions);
    }
}
