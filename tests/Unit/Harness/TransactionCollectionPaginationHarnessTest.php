<?php

namespace Tests\Unit\Harness;

use OnPay\API\Transaction\SimpleTransaction;
use OnPay\API\Transaction\TransactionCollection;
use OnPay\API\Util\Link;
use Tests\Support\ApiTestCase;
use Tests\Support\FixtureLoader;

/**
 * Harness coverage for a MIDDLE page of a multi-page transaction list.
 *
 * The existing collection harness only ever sees a faithful single page, where every
 * "collapsed" branch resolves to its default: wallet absent -> null, testmode false,
 * pagination links {} -> null URLs, and a single-entry links map. This fixture pins the
 * OTHER side of each so line-covered reads are proven to actually read:
 *   - a populated wallet ("applepay") vs an absent one (null),
 *   - testmode true vs false,
 *   - a MULTI-entry item links map (self + cancel) vs a single-entry self map,
 *   - pagination links.previous / links.next carrying real URLs.
 */
class TransactionCollectionPaginationHarnessTest extends ApiTestCase
{
    public function testMiddlePageParsesWalletTestModeLinksAndPagination(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('transaction/collection-page2'), 200, 'GET');

        $api = $this->createApi();
        $collection = $api->transaction()->getTransactions(2);

        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertInstanceOf(TransactionCollection::class, $collection);
        $this->assertCount(2, $collection->transactions);

        // Item 0: populated wallet + testmode true (the non-default side of both branches).
        $first = $collection->transactions[0];
        $this->assertInstanceOf(SimpleTransaction::class, $first);
        $this->assertSame(1003, $first->transactionNumber);
        $this->assertSame('applepay', $first->wallet);
        $this->assertTrue($first->testMode);

        // Item 0 links: a multi-entry map yields one Link per rel, in insertion order.
        $this->assertCount(2, $first->links);
        $this->assertInstanceOf(Link::class, $first->links[0]);
        $this->assertSame('self', $first->links[0]->rel);
        $this->assertSame('/transaction/323e4567-e89b-12d3-a456-426614174002', $first->links[0]->uri);
        $this->assertSame('cancel', $first->links[1]->rel);
        $this->assertSame('/transaction/323e4567-e89b-12d3-a456-426614174002/cancel', $first->links[1]->uri);

        // Item 1: absent wallet -> null, testmode false, single-entry self links map.
        $second = $collection->transactions[1];
        $this->assertSame(1004, $second->transactionNumber);
        $this->assertNull($second->wallet);
        $this->assertFalse($second->testMode);
        $this->assertCount(1, $second->links);
        $this->assertSame('self', $second->links[0]->rel);
        $this->assertSame('/transaction/423e4567-e89b-12d3-a456-426614174003', $second->links[0]->uri);

        // Pagination: populated previous/next links (the non-empty side of the {} branch).
        $this->assertSame(5, $collection->pagination->total);
        $this->assertSame(3, $collection->pagination->totalPages);
        $this->assertSame('https://api.onpay.io/v1/transaction/?page=1', $collection->pagination->previousUrl);
        $this->assertSame('https://api.onpay.io/v1/transaction/?page=3', $collection->pagination->nextUrl);
    }
}
