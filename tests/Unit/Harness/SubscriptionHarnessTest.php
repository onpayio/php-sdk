<?php

namespace Tests\Unit\Harness;

use OnPay\API\Subscription\DetailedSubscription;
use OnPay\API\Subscription\SubscriptionCollection;
use OnPay\API\Transaction\DetailedTransaction;
use Tests\Support\ApiTestCase;
use Tests\Support\FixtureLoader;

/**
 * End-to-end harness coverage for SubscriptionService.
 */
class SubscriptionHarnessTest extends ApiTestCase
{
    public function testGetSubscriptionSendsGetAndParsesDetailedSubscription(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('subscription/detailed'), 200, 'GET');

        $api = $this->createApi();
        $subscription = $api->subscription()->getSubscription('f47ac10b-58cc-4372-a567-0e02b2c3d479');

        $request = $this->http->getLastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame(
            self::BASE_URI . '/v1/subscription/f47ac10b-58cc-4372-a567-0e02b2c3d479',
            (string) $request->getUri()
        );
        $this->assertSame($this->expectedAuthorizationHeader(), $request->getHeaderLine('Authorization'));

        $this->assertInstanceOf(DetailedSubscription::class, $subscription);
        $this->assertSame('f47ac10b-58cc-4372-a567-0e02b2c3d479', $subscription->uuid);
        $this->assertCount(1, $subscription->history);
        $this->assertCount(1, $subscription->transactions);
    }

    public function testGetSubscriptionsParsesCollectionAndPagination(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('subscription/collection'), 200, 'GET');

        $api = $this->createApi();
        $collection = $api->subscription()->getSubscriptions();

        $this->assertInstanceOf(SubscriptionCollection::class, $collection);
        $this->assertCount(1, $collection->subscriptions);
        $this->assertSame(1, $collection->pagination->total);
    }

    public function testCancelSubscriptionSendsPost(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('subscription/detailed'), 200, 'POST');

        $api = $this->createApi();
        $subscription = $api->subscription()->cancelSubscription('f47ac10b-58cc-4372-a567-0e02b2c3d479');

        $request = $this->http->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(
            self::BASE_URI . '/v1/subscription/f47ac10b-58cc-4372-a567-0e02b2c3d479/cancel',
            (string) $request->getUri()
        );
        $this->assertInstanceOf(DetailedSubscription::class, $subscription);
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
        $this->assertSame(
            self::BASE_URI . '/v1/subscription/f47ac10b-58cc-4372-a567-0e02b2c3d479/authorize',
            (string) $request->getUri()
        );
        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame(12500, $body['data']['amount']);
        $this->assertSame('order-4242', $body['data']['order_id']);
        $this->assertInstanceOf(DetailedTransaction::class, $transaction);
    }
}
