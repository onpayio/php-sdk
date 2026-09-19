<?php

namespace Tests\Unit\Subscription;

use OnPay\API\Subscription\SubscriptionHistory;
use PHPUnit\Framework\TestCase;

/**
 * Direct coverage for {@see SubscriptionHistory}.
 *
 * Mirrors {@see \Tests\Unit\Transaction\TransactionHistoryTest}: fixtures only carry
 * "successful": true, and the absent-key default is also false, so both sides are pinned
 * here with distinct values. The fixtures likewise always carry every other field, so
 * the absent side of each read is pinned by the empty-payload case.
 */
class SubscriptionHistoryTest extends TestCase
{
    public function testAllFieldsAreReadWhenPresent(): void
    {
        $history = new SubscriptionHistory([
            'action' => 'created',
            'author' => 'system',
            'ip' => '203.0.113.11',
            'result_text' => 'Approved',
            'result_code' => '0',
            'successful' => true,
            'date_time' => '2026-09-18 09:00:00',
        ]);

        $this->assertSame('created', $history->action);
        $this->assertSame('system', $history->author);
        $this->assertSame('203.0.113.11', $history->ip);
        $this->assertSame('Approved', $history->resultText);
        $this->assertSame('0', $history->resultCode);
        $this->assertTrue($history->successful);
        $this->assertInstanceOf(\DateTime::class, $history->date);
        $this->assertSame('2026-09-18 09:00:00', $history->date->format('Y-m-d H:i:s'));
    }

    public function testEmptyPayloadLeavesEveryFieldAtItsDefault(): void
    {
        $history = new SubscriptionHistory([]);

        $this->assertNull($history->action);
        $this->assertNull($history->author);
        $this->assertNull($history->ip);
        $this->assertNull($history->resultText);
        $this->assertNull($history->resultCode);
        $this->assertFalse($history->successful);
        $this->assertNull($history->date);
    }

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
