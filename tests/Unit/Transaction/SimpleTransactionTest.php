<?php

namespace Tests\Unit\Transaction;

use OnPay\API\Transaction\SimpleTransaction;
use OnPay\API\Util\Link;
use PHPUnit\Framework\TestCase;

/**
 * Direct value-object coverage for {@see SimpleTransaction}, pinning both sides of every
 * optional-field read in the constructor.
 *
 * The harness fixtures always carry the identity fields (uuid, amount, currency_code,
 * order_id, status, transaction_number), so their absent side is never taken there. A
 * fully populated payload and an empty payload together prove present AND absent for
 * every field, and the defaults for the two bool flags.
 */
class SimpleTransactionTest extends TestCase
{
    public function testAllFieldsAreReadWhenPresent(): void
    {
        $transaction = new SimpleTransaction([
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            '3dsecure' => true,
            'acquirer' => 'clearhaus',
            'amount' => 12500,
            'card_type' => 'Visa',
            'charged' => 5000,
            'created' => '2026-09-18 10:00:00',
            'currency_code' => 208,
            'order_id' => 'order-4242',
            'refunded' => 2500,
            'status' => 'active',
            'transaction_number' => 1001,
            'wallet' => 'applepay',
            'has_cardholder_data' => true,
            'testmode' => true,
        ]);

        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $transaction->uuid);
        $this->assertTrue($transaction->threeDs);
        $this->assertSame('clearhaus', $transaction->acquirer);
        $this->assertSame(12500, $transaction->amount);
        $this->assertSame('Visa', $transaction->cardType);
        $this->assertSame(5000, $transaction->charged);
        $this->assertInstanceOf(\DateTime::class, $transaction->created);
        $this->assertSame('2026-09-18 10:00:00', $transaction->created->format('Y-m-d H:i:s'));
        $this->assertSame(208, $transaction->currencyCode);
        $this->assertSame('order-4242', $transaction->orderId);
        $this->assertSame(2500, $transaction->refunded);
        $this->assertSame('active', $transaction->status);
        $this->assertSame(1001, $transaction->transactionNumber);
        $this->assertSame('applepay', $transaction->wallet);
        $this->assertTrue($transaction->hasCardholderData);
        $this->assertTrue($transaction->testMode);
    }

    public function testEmptyPayloadLeavesEveryFieldAtItsDefault(): void
    {
        $transaction = new SimpleTransaction([]);

        $this->assertNull($transaction->uuid);
        $this->assertNull($transaction->threeDs);
        $this->assertNull($transaction->acquirer);
        $this->assertNull($transaction->amount);
        $this->assertNull($transaction->cardType);
        $this->assertNull($transaction->charged);
        $this->assertNull($transaction->created);
        $this->assertNull($transaction->currencyCode);
        $this->assertNull($transaction->orderId);
        $this->assertNull($transaction->refunded);
        $this->assertNull($transaction->status);
        $this->assertNull($transaction->transactionNumber);
        $this->assertNull($transaction->wallet);
        $this->assertFalse($transaction->hasCardholderData);
        $this->assertFalse($transaction->testMode);
        $this->assertNull($transaction->links);
    }

    public function testSetLinksBuildsOneLinkPerRel(): void
    {
        $transaction = new SimpleTransaction([]);
        $transaction->setLinks([
            'self' => '/transaction/abc',
            'subscription' => '/subscription/def',
        ]);

        $this->assertCount(2, $transaction->links);
        $this->assertContainsOnlyInstancesOf(Link::class, $transaction->links);
        $this->assertSame('self', $transaction->links[0]->rel);
        $this->assertSame('/transaction/abc', $transaction->links[0]->uri);
        $this->assertSame('subscription', $transaction->links[1]->rel);
        $this->assertSame('/subscription/def', $transaction->links[1]->uri);
    }
}
