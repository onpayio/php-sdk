<?php

namespace Tests\Unit\Harness;

use OnPay\API\Subscription\SimpleSubscription;
use OnPay\API\Subscription\SubscriptionCollection;
use OnPay\API\Util\Link;
use Tests\Support\ApiTestCase;
use Tests\Support\FixtureLoader;

/**
 * Harness coverage for a MIDDLE page of a multi-page subscription list.
 *
 * Mirrors {@see TransactionCollectionPaginationHarnessTest}: the single-page harness only
 * ever exercises the default side of the collapsed branches. This pins the other side:
 * populated wallet vs absent, testmode true vs false, a multi-entry item links map vs a
 * single-entry self map, and pagination previous/next URLs.
 */
class SubscriptionCollectionPaginationHarnessTest extends ApiTestCase
{
    public function testMiddlePageParsesWalletTestModeLinksAndPagination(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('subscription/collection-page2'), 200, 'GET');

        $api = $this->createApi();
        $collection = $api->subscription()->getSubscriptions(2);

        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertInstanceOf(SubscriptionCollection::class, $collection);
        $this->assertCount(2, $collection->subscriptions);

        // Item 0: populated wallet + testmode true.
        $first = $collection->subscriptions[0];
        $this->assertInstanceOf(SimpleSubscription::class, $first);
        $this->assertSame(5002, $first->subscriptionNumber);
        $this->assertSame('applepay', $first->wallet);
        $this->assertTrue($first->testMode);

        // Item 0 links: a multi-entry map yields one Link per rel, in insertion order.
        $this->assertCount(2, $first->links);
        $this->assertInstanceOf(Link::class, $first->links[0]);
        $this->assertSame('self', $first->links[0]->rel);
        $this->assertSame('/subscription/a47ac10b-58cc-4372-a567-0e02b2c3d480', $first->links[0]->uri);
        $this->assertSame('cancel', $first->links[1]->rel);
        $this->assertSame('/subscription/a47ac10b-58cc-4372-a567-0e02b2c3d480/cancel', $first->links[1]->uri);

        // Item 1: absent wallet -> null, testmode false, single-entry self links map.
        $second = $collection->subscriptions[1];
        $this->assertSame(5003, $second->subscriptionNumber);
        $this->assertNull($second->wallet);
        $this->assertFalse($second->testMode);
        $this->assertCount(1, $second->links);
        $this->assertSame('self', $second->links[0]->rel);
        $this->assertSame('/subscription/b47ac10b-58cc-4372-a567-0e02b2c3d481', $second->links[0]->uri);

        // Pagination: populated previous/next links.
        $this->assertSame(5, $collection->pagination->total);
        $this->assertSame(3, $collection->pagination->totalPages);
        $this->assertSame('https://api.onpay.io/v1/subscription/?page=1', $collection->pagination->previousUrl);
        $this->assertSame('https://api.onpay.io/v1/subscription/?page=3', $collection->pagination->nextUrl);
    }
}
