<?php

namespace Tests\Unit\Harness;

use OnPay\API\Transaction\DetailedTransaction;
use Tests\Support\ApiTestCase;
use Tests\Support\FixtureLoader;

/**
 * Harness coverage for a merchant-initiated (subscription-linked) detailed transaction.
 *
 * The stock transaction/detailed fixture carries subscription_number: null and
 * subscription_uuid: null, so both isset() reads on DetailedTransaction collapse to the
 * null default and the populated side is never proven. This variant carries a non-null
 * subscription_number (int) and subscription_uuid (string) so those reads are asserted.
 */
class TransactionFromSubscriptionHarnessTest extends ApiTestCase
{
    public function testDetailedTransactionCarriesSubscriptionLinkage(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('transaction/detailed-from-subscription'), 200, 'GET');

        $api = $this->createApi();
        $transaction = $api->transaction()->getTransaction('523e4567-e89b-12d3-a456-426614174004');

        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertInstanceOf(DetailedTransaction::class, $transaction);
        $this->assertSame(1005, $transaction->transactionNumber);

        // The populated side of both subscription reads (int number, string uuid).
        $this->assertSame(5001, $transaction->subscriptionNumber);
        $this->assertSame('f47ac10b-58cc-4372-a567-0e02b2c3d479', $transaction->subscriptionUuid);
    }
}
