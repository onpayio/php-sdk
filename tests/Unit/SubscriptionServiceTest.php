<?php

use OnPay\API\SubscriptionService;
use OnPay\API\Exception\ApiException;
use PHPUnit\Framework\TestCase;

class SubscriptionServiceTest extends TestCase {
    private $apiMock;
    private $service;

    protected function setUp(): void {
        $this->apiMock = $this->createMock(\OnPay\OnPayAPI::class);
        $this->service = new SubscriptionService($this->apiMock);
    }

    public function testGetSubscriptionThrowsOnEmptyId() {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Subscription ID must be provided');
        $this->service->getSubscription('');
    }

    public function testCancelSubscriptionThrowsOnEmptyId() {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Subscription ID must be provided');
        $this->service->cancelSubscription('');
    }

    public function testCreateTransactionFromSubscriptionThrowsOnEmptyUuid() {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Subscription UUID must be provided');
        $this->service->createTransactionFromSubscription('', 100, 'order123');
    }

    public function testGetSubscriptionReturnsDetailedSubscriptionOnValidId() {
        $uuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $mockResult = [
            'data' => ['uuid' => $uuid],
            'links' => ['self' => "/subscription/$uuid"]
        ];

        $this->apiMock->method('get')
            ->with("subscription/$uuid")
            ->willReturn($mockResult);

        $result = $this->service->getSubscription($uuid);

        $this->assertInstanceOf(\OnPay\API\Subscription\DetailedSubscription::class, $result);
        $this->assertEquals($uuid, $result->uuid);
    }

    public function testCancelSubscriptionReturnsDetailedSubscriptionOnValidId() {
        $uuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $mockResult = [
            'data' => ['uuid' => $uuid],
            'links' => ['self' => "/subscription/$uuid"]
        ];

        $this->apiMock->method('post')
            ->with("subscription/$uuid/cancel")
            ->willReturn($mockResult);

        $result = $this->service->cancelSubscription($uuid);

        $this->assertInstanceOf(\OnPay\API\Subscription\DetailedSubscription::class, $result);
        $this->assertEquals($uuid, $result->uuid);
    }

    public function testCreateTransactionFromSubscriptionReturnsDetailedTransactionOnValidUuid() {
        $uuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $mockResult = [
            'data' => ['uuid' => $uuid],
            'links' => ['self' => "/transaction/$uuid"]
        ];

        $this->apiMock->method('post')
            ->with("subscription/$uuid/authorize", $this->anything())
            ->willReturn($mockResult);

        $result = $this->service->createTransactionFromSubscription(
            $uuid,
            100,
            'order123'
        );

        $this->assertInstanceOf(\OnPay\API\Transaction\DetailedTransaction::class, $result);
        $this->assertEquals($uuid, $result->uuid);
    }
}