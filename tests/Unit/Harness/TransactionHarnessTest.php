<?php

namespace Tests\Unit\Harness;

use OnPay\API\Transaction\CardholderData;
use OnPay\API\Transaction\DetailedTransaction;
use OnPay\API\Transaction\SimpleTransaction;
use OnPay\API\Transaction\TransactionCollection;
use OnPay\API\Transaction\TransactionHistory;
use OnPay\API\Util\Link;
use Tests\Support\ApiTestCase;
use Tests\Support\FixtureLoader;

/**
 * End-to-end harness coverage for TransactionService: a real OnPayAPI driving the fake
 * PSR-18 client with canned fixtures, asserting both the outgoing request (method, path,
 * query, headers, body) and the fully parsed result graph against the fixture values.
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
        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame(
            self::BASE_URI . '/v1/transaction/123e4567-e89b-12d3-a456-426614174000',
            (string) $request->getUri()
        );
        $this->assertSame($this->expectedAuthorizationHeader(), $request->getHeaderLine('Authorization'));

        // Parsed result — the full field graph against the (now type-correct) fixture.
        $this->assertInstanceOf(DetailedTransaction::class, $transaction);
        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $transaction->uuid);
        $this->assertSame(1001, $transaction->transactionNumber);
        $this->assertSame('active', $transaction->status);
        $this->assertSame('order-4242', $transaction->orderId);
        $this->assertSame(12500, $transaction->amount);
        $this->assertSame(0, $transaction->charged);
        $this->assertSame(0, $transaction->refunded);
        $this->assertSame(208, $transaction->currencyCode);
        $this->assertSame('Visa', $transaction->cardType);
        $this->assertSame('clearhaus', $transaction->acquirer);
        $this->assertNull($transaction->wallet);
        $this->assertTrue($transaction->threeDs);
        $this->assertFalse($transaction->testMode);
        $this->assertSame(195, $transaction->fee);
        $this->assertSame(12, $transaction->expiryMonth);
        $this->assertSame(2030, $transaction->expiryYear);
        $this->assertSame('208', $transaction->cardCountry);
        $this->assertSame('457173', $transaction->cardBin);
        $this->assertSame('457173******0000', $transaction->cardMask);
        $this->assertSame('203.0.113.10', $transaction->ip);
        $this->assertSame('208', $transaction->ipCountry);
        $this->assertNull($transaction->subscriptionNumber);
        $this->assertNull($transaction->subscriptionUuid);
        $this->assertFalse($transaction->hasCardholderData);
        $this->assertNull($transaction->cardholderData);

        $this->assertInstanceOf(\DateTime::class, $transaction->created);
        $this->assertSame('2026-09-18 10:00:00', $transaction->created->format('Y-m-d H:i:s'));

        // History element graph (note TransactionHistory::$dateTime).
        $this->assertCount(1, $transaction->history);
        $history = $transaction->history[0];
        $this->assertInstanceOf(TransactionHistory::class, $history);
        $this->assertSame('created', $history->action);
        $this->assertSame(12500, $history->amount);
        $this->assertSame('system', $history->author);
        $this->assertSame('203.0.113.10', $history->ip);
        $this->assertSame('0', $history->resultCode);
        $this->assertSame('Approved', $history->resultText);
        $this->assertTrue($history->successful);
        $this->assertInstanceOf(\DateTime::class, $history->dateTime);
        $this->assertSame('2026-09-18 10:00:00', $history->dateTime->format('Y-m-d H:i:s'));

        // The must-preserve debug API is populated on the PSR-18 path too.
        $this->assertSame('GET', $api->getLastHttpRequest()->getMethod());
        $this->assertSame(200, $api->getLastHttpResponse()->getStatusCode());
    }

    public function testGetTransactionParsesCardholderData(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('transaction/detailed-with-cardholder'), 200, 'GET');

        $api = $this->createApi();
        $transaction = $api->transaction()->getTransaction('323e4567-e89b-12d3-a456-426614174002');

        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertTrue($transaction->hasCardholderData);
        $this->assertInstanceOf(CardholderData::class, $transaction->cardholderData);

        $cardholder = $transaction->cardholderData;
        $this->assertSame('Jens', $cardholder->firstName);
        $this->assertSame('Hansen', $cardholder->lastName);
        $this->assertSame('Att: Jens', $cardholder->attention);
        $this->assertSame('Acme ApS', $cardholder->company);
        $this->assertSame('Hovedgaden 1', $cardholder->address1);
        $this->assertSame('2. sal', $cardholder->address2);
        $this->assertSame('1000', $cardholder->postalCode);
        $this->assertSame('Koebenhavn', $cardholder->city);
        $this->assertSame(208, $cardholder->country);
        $this->assertSame('jens@example.test', $cardholder->email);
        $this->assertSame('+4512345678', $cardholder->phone);

        // Nested delivery_address object.
        $this->assertSame('Mette', $cardholder->deliveryFirstName);
        $this->assertSame('Nielsen', $cardholder->deliveryLastName);
        $this->assertSame('Beta ApS', $cardholder->deliveryCompany);
        $this->assertSame('Nyvej 2', $cardholder->deliveryAddress1);
        $this->assertSame('3. sal', $cardholder->deliveryAddress2);
        $this->assertSame('2000', $cardholder->deliveryPostalCode);
        $this->assertSame('Frederiksberg', $cardholder->deliveryCity);
        $this->assertSame(208, $cardholder->deliveryCountry);

        // The extra object is exposed verbatim.
        $this->assertSame(
            ['custom_reference' => 'ref-123', 'loyalty_tier' => 'gold'],
            $cardholder->extraFields
        );
    }

    public function testGetTransactionsParsesCollectionItemsAndPagination(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('transaction/collection'), 200, 'GET');

        $api = $this->createApi();
        $collection = $api->transaction()->getTransactions();

        $request = $this->http->getLastRequest();
        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertSame('GET', $request->getMethod());
        // http_build_query drops the null params, leaving only the normalized direction.
        $this->assertSame(self::BASE_URI . '/v1/transaction/?direction=DESC', (string) $request->getUri());

        $this->assertInstanceOf(TransactionCollection::class, $collection);
        $this->assertCount(2, $collection->transactions);

        $first = $collection->transactions[0];
        $this->assertInstanceOf(SimpleTransaction::class, $first);
        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $first->uuid);
        $this->assertSame(1001, $first->transactionNumber);
        $this->assertSame('active', $first->status);
        $this->assertSame('order-4242', $first->orderId);
        $this->assertSame(12500, $first->amount);
        $this->assertSame(0, $first->charged);
        $this->assertSame(0, $first->refunded);
        $this->assertSame(208, $first->currencyCode);
        $this->assertSame('Visa', $first->cardType);
        $this->assertTrue($first->threeDs);
        $this->assertFalse($first->testMode);
        $this->assertTrue($first->hasCardholderData);
        $this->assertInstanceOf(\DateTime::class, $first->created);
        $this->assertSame('2026-09-18 10:00:00', $first->created->format('Y-m-d H:i:s'));

        // Collection items carry a links array (unguarded setLinks() in the service).
        $this->assertInstanceOf(Link::class, $first->links[0]);
        $this->assertSame('self', $first->links[0]->rel);
        $this->assertSame('/transaction/123e4567-e89b-12d3-a456-426614174000', $first->links[0]->uri);

        $this->assertSame(1002, $collection->transactions[1]->transactionNumber);
        $this->assertFalse($collection->transactions[1]->hasCardholderData);

        // Pagination.
        $this->assertSame(2, $collection->pagination->total);
        $this->assertSame(1, $collection->pagination->totalPages);
        $this->assertNull($collection->pagination->nextUrl);
        $this->assertNull($collection->pagination->previousUrl);
    }

    public function testCaptureTransactionSendsPostWithJsonBody(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('transaction/detailed'), 200, 'POST');

        $api = $this->createApi();
        $transaction = $api->transaction()->captureTransaction('1001', 12500);

        $request = $this->http->getLastRequest();
        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(self::BASE_URI . '/v1/transaction/1001/capture', (string) $request->getUri());
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        // Authorization: Bearer is attached on the POST path too.
        $this->assertSame($this->expectedAuthorizationHeader(), $request->getHeaderLine('Authorization'));
        $this->assertSame(['data' => ['amount' => 12500]], json_decode((string) $request->getBody(), true));

        $this->assertInstanceOf(DetailedTransaction::class, $transaction);
        $this->assertSame(1001, $transaction->transactionNumber);
    }

    public function testCaptureTransactionSendsPostActionChargeAmountBody(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('transaction/detailed'), 200, 'POST');

        $api = $this->createApi();
        $transaction = $api->transaction()->captureTransaction('1001', null, 5000);

        $request = $this->http->getLastRequest();
        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertSame(self::BASE_URI . '/v1/transaction/1001/capture', (string) $request->getUri());
        $this->assertSame(['data' => ['postActionChargeAmount' => 5000]], json_decode((string) $request->getBody(), true));
        $this->assertInstanceOf(DetailedTransaction::class, $transaction);
    }

    public function testCancelTransactionSendsPostWithoutBody(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('transaction/detailed'), 200, 'POST');

        $api = $this->createApi();
        $transaction = $api->transaction()->cancelTransaction('1001');

        $request = $this->http->getLastRequest();
        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(self::BASE_URI . '/v1/transaction/1001/cancel', (string) $request->getUri());
        $this->assertInstanceOf(DetailedTransaction::class, $transaction);
        $this->assertSame(1001, $transaction->transactionNumber);
    }

    public function testRefundTransactionSendsPostWithJsonBody(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('transaction/detailed'), 200, 'POST');

        $api = $this->createApi();
        $transaction = $api->transaction()->refundTransaction('1001', 500);

        $request = $this->http->getLastRequest();
        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(self::BASE_URI . '/v1/transaction/1001/refund', (string) $request->getUri());
        $this->assertSame(['data' => ['amount' => 500]], json_decode((string) $request->getBody(), true));
        $this->assertInstanceOf(DetailedTransaction::class, $transaction);
    }

    public function testRefundTransactionSendsPostActionRefundAmountBody(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('transaction/detailed'), 200, 'POST');

        $api = $this->createApi();
        $transaction = $api->transaction()->refundTransaction('1001', null, 500);

        $request = $this->http->getLastRequest();
        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertSame(self::BASE_URI . '/v1/transaction/1001/refund', (string) $request->getUri());
        $this->assertSame(['data' => ['postActionRefundAmount' => 500]], json_decode((string) $request->getBody(), true));
        $this->assertInstanceOf(DetailedTransaction::class, $transaction);
    }
}
