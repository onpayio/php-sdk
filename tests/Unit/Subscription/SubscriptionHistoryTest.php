<?php

namespace Tests\Unit\Subscription;

use OnPay\API\Exception\ApiException;
use OnPay\API\Subscription\SubscriptionHistory;
use PHPUnit\Framework\TestCase;

/**
 * Direct coverage for {@see SubscriptionHistory}.
 *
 * Mirrors {@see \Tests\Unit\Transaction\TransactionHistoryTest}, minus `amount` (subscription
 * history elements carry no amount). action, author, ip and date_time are always present and
 * non-nullable — the constructor throws {@see ApiException} when one is absent. result_code /
 * result_text are optional; `successful` defaults to false when absent.
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

    public function testOptionalFieldsAreNullWhenAbsent(): void
    {
        $history = new SubscriptionHistory($this->requiredPayload());

        $this->assertNull($history->resultCode);
        $this->assertNull($history->resultText);
        $this->assertFalse($history->successful);
    }

    public function testThrowsWhenRequiredFieldMissing(): void
    {
        $this->expectException(ApiException::class);
        new SubscriptionHistory([]);
    }

    public function testSuccessfulTrueIsRead(): void
    {
        $history = new SubscriptionHistory(['successful' => true] + $this->requiredPayload());

        $this->assertTrue($history->successful);
    }

    public function testSuccessfulFalseIsRead(): void
    {
        $history = new SubscriptionHistory(['successful' => false] + $this->requiredPayload());

        $this->assertFalse($history->successful);
    }

    public function testSuccessfulDefaultsToFalseWhenAbsent(): void
    {
        $history = new SubscriptionHistory($this->requiredPayload());

        $this->assertFalse($history->successful);
    }

    /**
     * @return array<string, mixed>
     */
    private function requiredPayload(): array
    {
        return [
            'action' => 'created',
            'author' => 'system',
            'ip' => '203.0.113.11',
            'date_time' => '2026-09-18 09:00:00',
        ];
    }
}
