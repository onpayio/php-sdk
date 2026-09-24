<?php

namespace Tests\Unit\Transaction;

use OnPay\API\Exception\ApiException;
use OnPay\API\Transaction\CardholderData;
use OnPay\API\Transaction\DetailedTransaction;
use OnPay\API\Transaction\TransactionHistory;
use PHPUnit\Framework\TestCase;

/**
 * Direct value-object coverage for {@see DetailedTransaction}, pinning both sides of every
 * optional-field read the constructor adds on top of {@see \OnPay\API\Transaction\SimpleTransaction}.
 *
 * The detail-only fields (fee, expiry, card_*, ip*, subscription_*) are genuinely optional
 * (and `fee` is absent unless surcharge is enabled), so a payload carrying only the parent's
 * required core fields proves their null defaults; a fully populated payload proves the reads.
 */
class DetailedTransactionTest extends TestCase
{
    public function testAllDetailFieldsAreReadWhenPresent(): void
    {
        $transaction = new DetailedTransaction([
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
                $this->historyElement('created'),
                $this->historyElement('capture'),
            ],
            'subscription_number' => 5001,
            'subscription_uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        ] + $this->requiredCorePayload());

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
        $this->assertSame('a1b2c3d4-created-4000-8000-000000000000', $transaction->history[0]->uuid);

        $this->assertSame(5001, $transaction->subscriptionNumber);
        $this->assertSame('f47ac10b-58cc-4372-a567-0e02b2c3d479', $transaction->subscriptionUuid);
    }

    public function testDetailOptionalFieldsAreNullWhenAbsent(): void
    {
        $transaction = new DetailedTransaction(['history' => []] + $this->requiredCorePayload());

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
        ] + $this->requiredCorePayload());

        $this->assertFalse($transaction->hasCardholderData);
        $this->assertNull($transaction->cardholderData);
    }

    public function testThrowsWhenRequiredCoreFieldMissing(): void
    {
        $this->expectException(ApiException::class);
        new DetailedTransaction(['history' => []]);
    }

    public function testNonArrayHistoryEntryThrows(): void
    {
        // A non-array history entry collapses to [] (the ternary's false side), and a
        // history element with no required fields throws when built.
        $this->expectException(ApiException::class);
        new DetailedTransaction(['history' => ['not-an-array']] + $this->requiredCorePayload());
    }

    /**
     * The parent {@see \OnPay\API\Transaction\SimpleTransaction} required core fields.
     *
     * @return array<string, mixed>
     */
    private function requiredCorePayload(): array
    {
        return [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            '3dsecure' => true,
            'amount' => 12500,
            'charged' => 0,
            'created' => '2026-09-18 10:00:00',
            'currency_code' => 208,
            'refunded' => 0,
            'status' => 'active',
            'transaction_number' => 1001,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function historyElement(string $action): array
    {
        return [
            'action' => $action,
            'uuid' => 'a1b2c3d4-' . $action . '-4000-8000-000000000000',
            'amount' => 12500,
            'author' => 'system',
            'ip' => '203.0.113.10',
            'date_time' => '2026-09-18 10:00:00',
            'successful' => true,
        ];
    }
}
