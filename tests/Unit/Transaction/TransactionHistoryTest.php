<?php

namespace Tests\Unit\Transaction;

use OnPay\API\Transaction\TransactionHistory;
use PHPUnit\Framework\TestCase;

/**
 * Direct coverage for {@see TransactionHistory}.
 *
 * All history fixtures carry "successful": true, and the constructor's default when the
 * key is absent is ALSO false — so present-false and absent are indistinguishable. Both
 * sides are pinned here with distinct values so the read is proven either way. The
 * fixtures likewise always carry every other field, so the absent side of each read is
 * pinned by the empty-payload case.
 */
class TransactionHistoryTest extends TestCase
{
    public function testAllFieldsAreReadWhenPresent(): void
    {
        $history = new TransactionHistory([
            'action' => 'capture',
            'amount' => 12500,
            'author' => 'system',
            'ip' => '203.0.113.10',
            'result_code' => '0',
            'result_text' => 'Approved',
            'successful' => true,
            'date_time' => '2026-09-18 10:00:00',
        ]);

        $this->assertSame('capture', $history->action);
        $this->assertSame(12500, $history->amount);
        $this->assertSame('system', $history->author);
        $this->assertSame('203.0.113.10', $history->ip);
        $this->assertSame('0', $history->resultCode);
        $this->assertSame('Approved', $history->resultText);
        $this->assertTrue($history->successful);
        $this->assertInstanceOf(\DateTime::class, $history->dateTime);
        $this->assertSame('2026-09-18 10:00:00', $history->dateTime->format('Y-m-d H:i:s'));
    }

    public function testEmptyPayloadLeavesEveryFieldAtItsDefault(): void
    {
        $history = new TransactionHistory([]);

        $this->assertNull($history->action);
        $this->assertNull($history->amount);
        $this->assertNull($history->author);
        $this->assertNull($history->ip);
        $this->assertNull($history->resultCode);
        $this->assertNull($history->resultText);
        $this->assertFalse($history->successful);
        $this->assertNull($history->dateTime);
    }

    public function testSuccessfulTrueIsRead(): void
    {
        $history = new TransactionHistory([
            'action' => 'capture',
            'successful' => true,
        ]);

        $this->assertSame('capture', $history->action);
        $this->assertTrue($history->successful);
    }

    public function testSuccessfulFalseIsRead(): void
    {
        $history = new TransactionHistory([
            'action' => 'capture',
            'successful' => false,
        ]);

        $this->assertFalse($history->successful);
    }

    public function testSuccessfulDefaultsToFalseWhenAbsent(): void
    {
        $history = new TransactionHistory([
            'action' => 'capture',
        ]);

        $this->assertFalse($history->successful);
    }
}
