<?php

namespace Tests\Unit\Harness;

use OnPay\API\Transaction\DetailedTransaction;
use OnPay\API\Transaction\TransactionCollection;
use Tests\Support\ApiTestCase;
use Tests\Support\FixtureLoader;

/**
 * End-to-end harness coverage for TransactionService: real OnPayAPI driving the fake
 * PSR-18 client with canned fixtures, asserting both the outgoing request and the
 * parsed result object. Depth of coverage is the 100%-coverage ticket's job (6325);
 * this proves the harness works.
 */
class TransactionHarnessTest extends ApiTestCase
{
    public function testGetTransactionSendsGetAndParsesDetailedTransaction(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('transaction/detailed'), 200, 'GET');

        $api = $this->createApi();
        $transaction = $api->transaction()->getTransaction('123e4567-e89b-12d3-a456-426614174000');

        // Outgoing request (true wire shape via the captured PSR-7 request).
        $request = $this->http->getLastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame(
            self::BASE_URI . '/v1/transaction/123e4567-e89b-12d3-a456-426614174000',
            (string) $request->getUri()
        );
        $this->assertSame($this->expectedAuthorizationHeader(), $request->getHeaderLine('Authorization'));

        // Parsed result.
        $this->assertInstanceOf(DetailedTransaction::class, $transaction);
        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $transaction->uuid);
        $this->assertSame(12500, $transaction->amount);
        $this->assertSame('457173******0000', $transaction->cardMask);
        $this->assertCount(1, $transaction->history);

        // The must-preserve debug API is populated on the PSR-18 path too.
        $this->assertSame('GET', $api->getLastHttpRequest()->getMethod());
        $this->assertSame(200, $api->getLastHttpResponse()->getStatusCode());
    }

    public function testGetTransactionsParsesCollectionAndPagination(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('transaction/collection'), 200, 'GET');

        $api = $this->createApi();
        $collection = $api->transaction()->getTransactions();

        $request = $this->http->getLastRequest();
        $this->assertStringContainsString('/v1/transaction/', (string) $request->getUri());
        $this->assertStringContainsString('direction=DESC', (string) $request->getUri());

        $this->assertInstanceOf(TransactionCollection::class, $collection);
        $this->assertCount(2, $collection->transactions);
        $this->assertSame(2, $collection->pagination->total);
        $this->assertSame(1, $collection->pagination->totalPages);
    }

    public function testCaptureTransactionSendsPostWithJsonBody(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('transaction/detailed'), 200, 'POST');

        $api = $this->createApi();
        $transaction = $api->transaction()->captureTransaction('1001', 12500);

        $request = $this->http->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(self::BASE_URI . '/v1/transaction/1001/capture', (string) $request->getUri());
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame(['data' => ['amount' => 12500]], json_decode((string) $request->getBody(), true));

        $this->assertInstanceOf(DetailedTransaction::class, $transaction);
    }

    public function testCancelTransactionSendsPostWithoutBody(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('transaction/detailed'), 200, 'POST');

        $api = $this->createApi();
        $transaction = $api->transaction()->cancelTransaction('1001');

        $request = $this->http->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(self::BASE_URI . '/v1/transaction/1001/cancel', (string) $request->getUri());
        $this->assertInstanceOf(DetailedTransaction::class, $transaction);
    }

    public function testRefundTransactionSendsPostWithJsonBody(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('transaction/detailed'), 200, 'POST');

        $api = $this->createApi();
        $transaction = $api->transaction()->refundTransaction('1001', 500);

        $request = $this->http->getLastRequest();
        $this->assertSame(self::BASE_URI . '/v1/transaction/1001/refund', (string) $request->getUri());
        $this->assertSame(['data' => ['amount' => 500]], json_decode((string) $request->getBody(), true));
        $this->assertInstanceOf(DetailedTransaction::class, $transaction);
    }
}
