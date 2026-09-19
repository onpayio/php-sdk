<?php

namespace Tests\Unit;

use OnPay\API\TransactionService;
use OnPay\API\Exception\ApiException;
use PHPUnit\Framework\TestCase;

/**
 * Guard/validation unit tests for TransactionService. The success/parse paths are covered
 * end-to-end by {@see \Tests\Unit\Harness\TransactionHarnessTest}; this file keeps only the
 * unique argument-guard behaviour and the query normalization that runs before/around the
 * HTTP call.
 */
class TransactionServiceTest extends TestCase {
    private $apiMock;
    private $service;

    protected function setUp(): void {
        $this->apiMock = $this->createMock(\OnPay\OnPayAPI::class);
        $this->service = new TransactionService($this->apiMock);
    }

    public function testGetTransactionThrowsOnEmptyIdentifier() {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Transaction number must be provided');
        $this->service->getTransaction('');
    }

    public function testCaptureTransactionThrowsOnEmptyTransactionNumber() {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Transaction number must be provided');
        $this->service->captureTransaction('');
    }

    public function testCancelTransactionThrowsOnEmptyTransactionNumber() {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Transaction number must be provided');
        $this->service->cancelTransaction('');
    }

    public function testRefundTransactionThrowsOnEmptyTransactionNumber() {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Transaction number must be provided');
        $this->service->refundTransaction('');
    }

    public function testCaptureTransactionThrowsWhenAmountAndPostActionChargeAmountBothProvided() {
        $this->expectException(ApiException::class);
        // Single-quoted: the src message is a literal, not interpolated.
        $this->expectExceptionMessage('$amount and $postActionChargeAmount are mutually exclusive and can not both be used together');
        $this->service->captureTransaction('1001', 100, 200);
    }

    public function testRefundTransactionThrowsWhenAmountAndPostActionRefundAmountBothProvided() {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('$amount and $postActionRefundAmount are mutually exclusive and can not both be used together');
        $this->service->refundTransaction('1001', 100, 200);
    }

    public function testGetTransactionsNormalizesLowercaseDirectionToAsc() {
        $this->assertSame(
            'transaction/?direction=ASC',
            $this->captureListDirection('asc')
        );
    }

    public function testGetTransactionsKeepsUppercaseAscDirection() {
        $this->assertSame(
            'transaction/?direction=ASC',
            $this->captureListDirection('ASC')
        );
    }

    public function testGetTransactionsNormalizesGarbageDirectionToDesc() {
        $this->assertSame(
            'transaction/?direction=DESC',
            $this->captureListDirection('not-a-direction')
        );
    }

    public function testGetTransactionsDefaultsToDescDirection() {
        $this->assertSame(
            'transaction/?direction=DESC',
            $this->captureListDirection()
        );
    }

    /**
     * Drive getTransactions() through the mocked API, capturing the URL passed to get(),
     * so the direction-normalization branch can be asserted on the outgoing query.
     */
    private function captureListDirection(?string $direction = null): string {
        $captured = null;
        $this->apiMock->method('get')->willReturnCallback(function ($url) use (&$captured) {
            $captured = $url;
            return ['data' => [], 'meta' => ['pagination' => []]];
        });

        if (null === $direction) {
            $this->service->getTransactions();
        } else {
            $this->service->getTransactions(null, null, null, null, null, null, null, $direction);
        }

        $this->assertNotNull($captured, 'api->get() was never invoked');

        return $captured;
    }
}
