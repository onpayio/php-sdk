<?php

namespace Tests\Unit;

use OnPay\API\SubscriptionService;
use OnPay\API\Exception\ApiException;
use PHPUnit\Framework\TestCase;

/**
 * Guard/validation unit tests for SubscriptionService. The success/parse paths are covered
 * end-to-end by {@see \Tests\Unit\Harness\SubscriptionHarnessTest}; this file keeps only the
 * unique argument-guard behaviour that runs before any HTTP call.
 */
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
}
