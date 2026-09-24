<?php

namespace Tests\Unit\Transaction;

use OnPay\API\Exception\ApiException;
use OnPay\API\Transaction\TransactionHistory;
use PHPUnit\Framework\TestCase;

/**
 * Direct coverage for {@see TransactionHistory}.
 *
 * The API guarantees action, amount, author, uuid, ip and date_time on every history
 * element, so they are non-nullable and the constructor throws {@see ApiException} when one
 * is absent. result_code / result_text are optional; `successful` defaults to false when
 * absent, so both sides are pinned here with distinct values.
 */
class TransactionHistoryTest extends TestCase
{
    public function testAllFieldsAreReadWhenPresent(): void
    {
        $history = new TransactionHistory([
            'action' => 'capture',
            'amount' => 12500,
            'author' => 'system',
            'uuid' => 'a1b2c3d4-0000-4000-8000-000000000000',
            'ip' => '203.0.113.10',
            'result_code' => '0',
            'result_text' => 'Approved',
            'successful' => true,
            'date_time' => '2026-09-18 10:00:00',
        ]);

        $this->assertSame('capture', $history->action);
        $this->assertSame(12500, $history->amount);
        $this->assertSame('system', $history->author);
        $this->assertSame('a1b2c3d4-0000-4000-8000-000000000000', $history->uuid);
        $this->assertSame('203.0.113.10', $history->ip);
        $this->assertSame('0', $history->resultCode);
        $this->assertSame('Approved', $history->resultText);
        $this->assertTrue($history->successful);
        $this->assertInstanceOf(\DateTime::class, $history->dateTime);
        $this->assertSame('2026-09-18 10:00:00', $history->dateTime->format('Y-m-d H:i:s'));
    }

    public function testOptionalFieldsAreNullWhenAbsent(): void
    {
        $history = new TransactionHistory($this->requiredPayload());

        $this->assertNull($history->resultCode);
        $this->assertNull($history->resultText);
        $this->assertFalse($history->successful);
    }

    public function testThrowsWhenRequiredFieldMissing(): void
    {
        $this->expectException(ApiException::class);
        new TransactionHistory([]);
    }

    public function testSuccessfulTrueIsRead(): void
    {
        $history = new TransactionHistory(['successful' => true] + $this->requiredPayload());

        $this->assertTrue($history->successful);
    }

    public function testSuccessfulFalseIsRead(): void
    {
        $history = new TransactionHistory(['successful' => false] + $this->requiredPayload());

        $this->assertFalse($history->successful);
    }

    public function testSuccessfulDefaultsToFalseWhenAbsent(): void
    {
        $history = new TransactionHistory($this->requiredPayload());

        $this->assertFalse($history->successful);
    }

    /**
     * @return array<string, mixed>
     */
    private function requiredPayload(): array
    {
        return [
            'action' => 'capture',
            'amount' => 12500,
            'author' => 'system',
            'uuid' => 'a1b2c3d4-0000-4000-8000-000000000000',
            'ip' => '203.0.113.10',
            'date_time' => '2026-09-18 10:00:00',
        ];
    }
}
