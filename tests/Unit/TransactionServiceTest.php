<?php

namespace Tests\Unit;

use OnPay\API\TransactionService;
use OnPay\API\Exception\ApiException;
use Tests\Support\ApiTestCase;

/**
 * Guard/validation unit tests for TransactionService. The success/parse paths are covered
 * end-to-end by {@see \Tests\Unit\Harness\TransactionHarnessTest}; this file keeps only the
 * unique argument-guard behaviour and the query normalization that runs before/around the
 * HTTP call.
 *
 * The service is taken from a real OnPayAPI wired to the {@see \Tests\Support\FakeHttpClient}
 * rather than from a mocked ApiClient, so the direction assertions below are made against
 * the URI that actually went on the wire.
 */
class TransactionServiceTest extends ApiTestCase {
    private TransactionService $service;

    protected function setUp(): void {
        parent::setUp();
        $this->service = $this->createApi()->transaction();
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
            self::BASE_URI . '/v1/transaction/?direction=ASC',
            $this->captureListDirection('asc')
        );
    }

    public function testGetTransactionsKeepsUppercaseAscDirection() {
        $this->assertSame(
            self::BASE_URI . '/v1/transaction/?direction=ASC',
            $this->captureListDirection('ASC')
        );
    }

    public function testGetTransactionsNormalizesGarbageDirectionToDesc() {
        $this->assertSame(
            self::BASE_URI . '/v1/transaction/?direction=DESC',
            $this->captureListDirection('not-a-direction')
        );
    }

    public function testGetTransactionsDefaultsToDescDirection() {
        $this->assertSame(
            self::BASE_URI . '/v1/transaction/?direction=DESC',
            $this->captureListDirection()
        );
    }

    public function testGetTransactionsThrowsOnNonArrayItem() {
        // A non-array list item collapses to [] (the ternary's false side); an empty
        // transaction payload has no required fields, so building it throws.
        $this->http->willReturnJson([
            'data' => ['not-an-array'],
            'meta' => ['pagination' => ['total' => 0, 'total_pages' => 0]],
        ]);

        $this->expectException(ApiException::class);
        $this->service->getTransactions();
    }

    /**
     * Drive getTransactions() end-to-end against the fake client and return the URI that
     * was actually requested, so the direction-normalization branch can be asserted on the
     * outgoing query.
     */
    private function captureListDirection(?string $direction = null): string {
        $this->http->willReturnJson([
            'data' => [],
            'meta' => ['pagination' => ['total' => 0, 'total_pages' => 0]],
        ]);

        if (null === $direction) {
            $this->service->getTransactions();
        } else {
            $this->service->getTransactions(null, null, null, null, null, null, null, $direction);
        }

        $request = $this->http->getLastRequest();
        $this->assertNotNull($request, 'no HTTP request was made');

        return (string) $request->getUri();
    }
}
