<?php

namespace Tests\Unit\Transaction;

use OnPay\API\Transaction\CardholderData;
use OnPay\API\Transaction\DetailedTransaction;
use OnPay\API\Transaction\TransactionHistory;
use PHPUnit\Framework\TestCase;

/**
 * Direct value-object coverage for {@see DetailedTransaction}, pinning both sides of every
 * optional-field read the constructor adds on top of {@see \OnPay\API\Transaction\SimpleTransaction}.
 *
 * The detailed fixtures always carry the card/ip detail fields (fee, expiry, card_*, ip*),
 * so their absent side is never taken there. A minimal payload (only the mandatory
 * `history` list) proves the null defaults; a fully populated payload proves the reads.
 */
class DetailedTransactionTest extends TestCase
{
    public function testAllDetailFieldsAreReadWhenPresent(): void
    {
        $transaction = new DetailedTransaction([
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'fee' => 195,
            'expiry_year' => 2030,
            'expiry_month' => 12,
            'card_country' => '208',
            'card_bin' => '457173',
            'card_mask' => '457173******0000',
            'ip' => '203.0.113.10',
            'ip_country' => '208',
            'has_cardholder_data' => true,
            'cardholder_data' => ['first_name' => 'Jens'],
            'history' => [
                ['action' => 'created', 'successful' => true],
                ['action' => 'capture', 'successful' => true],
            ],
            'subscription_number' => 5001,
            'subscription_uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        ]);

        // Parent constructor still runs first.
        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $transaction->uuid);

        $this->assertSame(195, $transaction->fee);
        $this->assertSame(2030, $transaction->expiryYear);
        $this->assertSame(12, $transaction->expiryMonth);
        $this->assertSame('208', $transaction->cardCountry);
        $this->assertSame('457173', $transaction->cardBin);
        $this->assertSame('457173******0000', $transaction->cardMask);
        $this->assertSame('203.0.113.10', $transaction->ip);
        $this->assertSame('208', $transaction->ipCountry);
        $this->assertTrue($transaction->hasCardholderData);

        $this->assertInstanceOf(CardholderData::class, $transaction->cardholderData);
        $this->assertSame('Jens', $transaction->cardholderData->firstName);

        $this->assertCount(2, $transaction->history);
        $this->assertContainsOnlyInstancesOf(TransactionHistory::class, $transaction->history);
        $this->assertSame('created', $transaction->history[0]->action);
        $this->assertSame('capture', $transaction->history[1]->action);

        $this->assertSame(5001, $transaction->subscriptionNumber);
        $this->assertSame('f47ac10b-58cc-4372-a567-0e02b2c3d479', $transaction->subscriptionUuid);
    }

    public function testMinimalPayloadLeavesEveryDetailFieldAtItsDefault(): void
    {
        $transaction = new DetailedTransaction(['history' => []]);

        $this->assertNull($transaction->fee);
        $this->assertNull($transaction->expiryYear);
        $this->assertNull($transaction->expiryMonth);
        $this->assertNull($transaction->cardCountry);
        $this->assertNull($transaction->cardBin);
        $this->assertNull($transaction->cardMask);
        $this->assertNull($transaction->ip);
        $this->assertNull($transaction->ipCountry);
        $this->assertFalse($transaction->hasCardholderData);
        $this->assertNull($transaction->cardholderData);
        $this->assertSame([], $transaction->history);
        $this->assertNull($transaction->subscriptionNumber);
        $this->assertNull($transaction->subscriptionUuid);
    }

    public function testExplicitNullCardholderDataIsNotWrapped(): void
    {
        // The API emits "cardholder_data": null when none is stored; isset() is false for a
        // null value, so no CardholderData object is built.
        $transaction = new DetailedTransaction([
            'has_cardholder_data' => false,
            'cardholder_data' => null,
            'history' => [],
        ]);

        $this->assertFalse($transaction->hasCardholderData);
        $this->assertNull($transaction->cardholderData);
    }

    public function testNonArrayHistoryEntryYieldsEmptyHistoryItem(): void
    {
        $transaction = new DetailedTransaction(['history' => ['not-an-array']]);

        $this->assertCount(1, $transaction->history);
        $this->assertInstanceOf(TransactionHistory::class, $transaction->history[0]);
        $this->assertNull($transaction->history[0]->action);
        $this->assertFalse($transaction->history[0]->successful);
    }
}
