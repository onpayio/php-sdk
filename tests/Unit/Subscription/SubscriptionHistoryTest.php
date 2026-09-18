<?php

namespace Tests\Unit\Subscription;

use OnPay\API\Subscription\SubscriptionHistory;
use PHPUnit\Framework\TestCase;

/**
 * Direct coverage for {@see SubscriptionHistory::$successful}.
 *
 * Mirrors {@see \Tests\Unit\Transaction\TransactionHistoryTest}: fixtures only carry
 * "successful": true, and the absent-key default is also false, so both sides are pinned
 * here with distinct values.
 */
class SubscriptionHistoryTest extends TestCase
{
    public function testSuccessfulTrueIsRead(): void
    {
        $history = new SubscriptionHistory([
            'action' => 'created',
            'successful' => true,
        ]);

        $this->assertSame('created', $history->action);
        $this->assertTrue($history->successful);
    }

    public function testSuccessfulFalseIsRead(): void
    {
        $history = new SubscriptionHistory([
            'action' => 'created',
            'successful' => false,
        ]);

        $this->assertFalse($history->successful);
    }

    public function testSuccessfulDefaultsToFalseWhenAbsent(): void
    {
        $history = new SubscriptionHistory([
            'action' => 'created',
        ]);

        $this->assertFalse($history->successful);
    }
}
