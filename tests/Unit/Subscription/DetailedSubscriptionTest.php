<?php

namespace Tests\Unit\Subscription;

use OnPay\API\Exception\ApiException;
use OnPay\API\Subscription\DetailedSubscription;
use OnPay\API\Subscription\SubscriptionHistory;
use OnPay\API\Transaction\SimpleTransaction;
use PHPUnit\Framework\TestCase;

/**
 * Direct value-object coverage for {@see DetailedSubscription}, pinning both sides of every
 * optional-field read the constructor adds on top of {@see \OnPay\API\Subscription\SimpleSubscription}.
 *
 * The detail-only fields (expiry, card_*, ip*, fee) are genuinely optional, so a payload
 * carrying only the parent's required core fields proves their null defaults; a fully
 * populated payload proves the reads. Nested history and transaction elements carry their
 * own always-present fields.
 */
class DetailedSubscriptionTest extends TestCase
{
    public function testAllDetailFieldsAreReadWhenPresent(): void
    {
        $subscription = new DetailedSubscription([
            'expiry_month' => 11,
            'expiry_year' => 2029,
            'card_country' => '208',
            'card_bin' => '457173',
            'ip' => '203.0.113.11',
            'ip_country' => '208',
            'fee' => 95,
            'history' => [
                $this->historyElement('created'),
                $this->historyElement('authorize'),
            ],
            'transactions' => [
                $this->transactionElement('tx-1', 12500),
                $this->transactionElement('tx-2', 500),
            ],
        ] + $this->requiredCorePayload());

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

    public function testDetailOptionalFieldsAreNullWhenAbsent(): void
    {
        $subscription = new DetailedSubscription(
            ['history' => [], 'transactions' => []] + $this->requiredCorePayload()
        );

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

    public function testThrowsWhenRequiredCoreFieldMissing(): void
    {
        $this->expectException(ApiException::class);
        new DetailedSubscription(['history' => [], 'transactions' => []]);
    }

    /**
     * The parent {@see \OnPay\API\Subscription\SimpleSubscription} required core fields.
     *
     * @return array<string, mixed>
     */
    private function requiredCorePayload(): array
    {
        return [
            'uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            '3dsecure' => true,
            'currency_code' => 208,
            'subscription_number' => 5001,
            'status' => 'active',
            'created' => '2026-09-18 09:00:00',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function historyElement(string $action): array
    {
        return [
            'action' => $action,
            'author' => 'system',
            'ip' => '203.0.113.11',
            'date_time' => '2026-09-18 09:00:00',
            'successful' => true,
        ];
    }

    /**
     * A full simple-transaction element as the subscription detail response embeds them.
     *
     * @return array<string, mixed>
     */
    private function transactionElement(string $uuid, int $amount): array
    {
        return [
            'uuid' => $uuid,
            '3dsecure' => true,
            'amount' => $amount,
            'charged' => 0,
            'created' => '2026-09-18 10:00:00',
            'currency_code' => 208,
            'refunded' => 0,
            'status' => 'active',
            'transaction_number' => 1001,
        ];
    }
}
