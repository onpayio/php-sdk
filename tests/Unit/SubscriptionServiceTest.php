<?php

namespace Tests\Unit;

use OnPay\API\SubscriptionService;
use OnPay\API\Exception\ApiException;
use Tests\Support\ApiTestCase;

/**
 * Guard/validation unit tests for SubscriptionService. The success/parse paths are covered
 * end-to-end by {@see \Tests\Unit\Harness\SubscriptionHarnessTest}; this file keeps only the
 * unique argument-guard behaviour that runs before any HTTP call.
 *
 * The service is taken from a real OnPayAPI wired to the {@see \Tests\Support\FakeHttpClient}
 * rather than from a mocked ApiClient: the guards short-circuit before any request, so the
 * fake is never asked for a response.
 */
class SubscriptionServiceTest extends ApiTestCase {
    private SubscriptionService $service;

    protected function setUp(): void {
        parent::setUp();
        $this->service = $this->createApi()->subscription();
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
