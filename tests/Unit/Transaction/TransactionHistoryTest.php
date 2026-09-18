<?php

namespace Tests\Unit\Transaction;

use OnPay\API\Transaction\TransactionHistory;
use PHPUnit\Framework\TestCase;

/**
 * Direct coverage for {@see TransactionHistory::$successful}.
 *
 * All history fixtures carry "successful": true, and the constructor's default when the
 * key is absent is ALSO false — so present-false and absent are indistinguishable. Both
 * sides are pinned here with distinct values so the read is proven either way.
 */
class TransactionHistoryTest extends TestCase
{
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
