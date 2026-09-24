<?php

namespace Tests\Unit\Harness;

use OnPay\API\Subscription\DetailedSubscription;
use OnPay\API\Subscription\SimpleSubscription;
use OnPay\API\Subscription\SubscriptionCollection;
use OnPay\API\Subscription\SubscriptionHistory;
use OnPay\API\Transaction\DetailedTransaction;
use OnPay\API\Transaction\SimpleTransaction;
use OnPay\API\Util\Link;
use Tests\Support\ApiTestCase;
use Tests\Support\FixtureLoader;

/**
 * End-to-end harness coverage for SubscriptionService, asserting the outgoing request and
 * the full parsed field graph against the (type-correct) fixtures.
 */
class SubscriptionHarnessTest extends ApiTestCase
{
    public function testGetSubscriptionSendsGetAndParsesDetailedSubscription(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('subscription/detailed'), 200, 'GET');

        $api = $this->createApi();
        $subscription = $api->subscription()->getSubscription('f47ac10b-58cc-4372-a567-0e02b2c3d479');

        $request = $this->http->getLastRequest();
        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame(
            self::BASE_URI . '/v1/subscription/f47ac10b-58cc-4372-a567-0e02b2c3d479',
            (string) $request->getUri()
        );
        $this->assertSame($this->expectedAuthorizationHeader(), $request->getHeaderLine('Authorization'));

        $this->assertInstanceOf(DetailedSubscription::class, $subscription);
        $this->assertSame('f47ac10b-58cc-4372-a567-0e02b2c3d479', $subscription->uuid);
        $this->assertSame(5001, $subscription->subscriptionNumber);
        $this->assertSame('active', $subscription->status);
        $this->assertSame('order-9001', $subscription->orderId);
        $this->assertSame(208, $subscription->currencyCode);
        $this->assertSame('Visa', $subscription->cardType);
        $this->assertSame('clearhaus', $subscription->acquirer);
        $this->assertNull($subscription->wallet);
        $this->assertTrue($subscription->threeDs);
        $this->assertFalse($subscription->testMode);
        $this->assertSame(11, $subscription->expiryMonth);
        $this->assertSame(2029, $subscription->expiryYear);
        $this->assertSame('208', $subscription->cardCountry);
        $this->assertSame('457173', $subscription->cardBin);
        $this->assertSame('203.0.113.11', $subscription->ip);
        $this->assertSame('208', $subscription->ipCountry);
        $this->assertInstanceOf(\DateTime::class, $subscription->created);
        $this->assertSame('2026-09-18 09:00:00', $subscription->created->format('Y-m-d H:i:s'));

        // Subscription history element (note SubscriptionHistory::$date, no amount).
        $this->assertCount(1, $subscription->history);
        $history = $subscription->history[0];
        $this->assertInstanceOf(SubscriptionHistory::class, $history);
        $this->assertSame('created', $history->action);
        $this->assertSame('system', $history->author);
        $this->assertSame('203.0.113.11', $history->ip);
        $this->assertSame('0', $history->resultCode);
        $this->assertSame('Approved', $history->resultText);
        $this->assertTrue($history->successful);
        $this->assertInstanceOf(\DateTime::class, $history->date);
        $this->assertSame('2026-09-18 09:00:00', $history->date->format('Y-m-d H:i:s'));

        // Nested simple transactions.
        $this->assertCount(1, $subscription->transactions);
        $transaction = $subscription->transactions[0];
        $this->assertInstanceOf(SimpleTransaction::class, $transaction);
        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $transaction->uuid);
        $this->assertSame(1001, $transaction->transactionNumber);
        $this->assertSame('active', $transaction->status);
        $this->assertSame('order-9001', $transaction->orderId);
        $this->assertSame(12500, $transaction->amount);
        $this->assertSame(208, $transaction->currencyCode);
        $this->assertInstanceOf(\DateTime::class, $transaction->created);
        $this->assertSame('2026-09-18 10:00:00', $transaction->created->format('Y-m-d H:i:s'));
    }

    public function testGetSubscriptionsSendsGetAndParsesCollection(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('subscription/collection'), 200, 'GET');

        $api = $this->createApi();
        $collection = $api->subscription()->getSubscriptions();

        $request = $this->http->getLastRequest();
        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame(self::BASE_URI . '/v1/subscription/?direction=DESC', (string) $request->getUri());

        $this->assertInstanceOf(SubscriptionCollection::class, $collection);
        $this->assertCount(1, $collection->subscriptions);

        $item = $collection->subscriptions[0];
        $this->assertInstanceOf(SimpleSubscription::class, $item);
        $this->assertSame('f47ac10b-58cc-4372-a567-0e02b2c3d479', $item->uuid);
        $this->assertSame(5001, $item->subscriptionNumber);
        $this->assertSame('active', $item->status);
        $this->assertSame('order-9001', $item->orderId);
        $this->assertSame(208, $item->currencyCode);
        $this->assertSame('Visa', $item->cardType);
        $this->assertSame('clearhaus', $item->acquirer);
        $this->assertTrue($item->threeDs);
        $this->assertFalse($item->testMode);
        $this->assertInstanceOf(\DateTime::class, $item->created);
        $this->assertSame('2026-09-18 09:00:00', $item->created->format('Y-m-d H:i:s'));

        // Collection items carry a links array (unguarded setLinks() in the service).
        $this->assertInstanceOf(Link::class, $item->links[0]);
        $this->assertSame('self', $item->links[0]->rel);
        $this->assertSame('/subscription/f47ac10b-58cc-4372-a567-0e02b2c3d479', $item->links[0]->uri);

        $this->assertSame(1, $collection->pagination->total);
        $this->assertSame(1, $collection->pagination->totalPages);
        $this->assertNull($collection->pagination->nextUrl);
        $this->assertNull($collection->pagination->previousUrl);
    }

    public function testCancelSubscriptionSendsPost(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('subscription/detailed'), 200, 'POST');

        $api = $this->createApi();
        $subscription = $api->subscription()->cancelSubscription('f47ac10b-58cc-4372-a567-0e02b2c3d479');

        $request = $this->http->getLastRequest();
        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(
            self::BASE_URI . '/v1/subscription/f47ac10b-58cc-4372-a567-0e02b2c3d479/cancel',
            (string) $request->getUri()
        );
        $this->assertInstanceOf(DetailedSubscription::class, $subscription);
        $this->assertSame(5001, $subscription->subscriptionNumber);
    }

    public function testCreateTransactionFromSubscriptionSendsAuthorizeWithBody(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('transaction/detailed'), 200, 'POST');

        $api = $this->createApi();
        $transaction = $api->subscription()->createTransactionFromSubscription(
            'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            12500,
            'order-4242'
        );

        $request = $this->http->getLastRequest();
        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(
            self::BASE_URI . '/v1/subscription/f47ac10b-58cc-4372-a567-0e02b2c3d479/authorize',
            (string) $request->getUri()
        );
        $this->assertSame($this->expectedAuthorizationHeader(), $request->getHeaderLine('Authorization'));
        $this->assertSame(
            ['data' => [
                'amount' => 12500,
                'order_id' => 'order-4242',
                'surcharge_enabled' => false,
                'surcharge_vat_rate' => 0,
            ]],
            json_decode((string) $request->getBody(), true)
        );
        $this->assertInstanceOf(DetailedTransaction::class, $transaction);
        $this->assertSame(1001, $transaction->transactionNumber);
    }
}
