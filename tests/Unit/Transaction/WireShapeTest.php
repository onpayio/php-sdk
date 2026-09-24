<?php

namespace Tests\Unit\Transaction;

use OnPay\API\Transaction\CardholderData;
use OnPay\API\Transaction\DetailedTransaction;
use Tests\Support\ApiTestCase;
use Tests\Support\FixtureLoader;

/**
 * Anti-regression cover for the response shapes that are easy to model wrongly.
 * {@see \Tests\Unit\Harness\TransactionHarnessTest} covers the ordinary parse path.
 */
class WireShapeTest extends ApiTestCase
{
    /** `has_cardholder_data: true` with `cardholder_data: null` is valid, not a contradiction. */
    public function testCardholderDataCanBeNullWhileTheFlagIsTrue(): void
    {
        $transaction = $this->detailedTransactionFrom([
            'has_cardholder_data' => true,
            'cardholder_data' => null,
        ]);

        $this->assertTrue($transaction->hasCardholderData);
        $this->assertNull($transaction->cardholderData);
    }

    /** `extra` arrives as a JSON array when empty and an object when populated. */
    public function testExtraIsAnArrayWhetherTheWireSendsAnArrayOrAnObject(): void
    {
        // Empty: the wire sends [].
        $empty = $this->cardholderDataFrom(['extra' => []]);
        $this->assertSame([], $empty->extraFields);

        // Populated: the wire sends {}.
        $populated = $this->cardholderDataFrom(['extra' => ['loyalty_tier' => 'gold']]);
        $this->assertSame(['loyalty_tier' => 'gold'], $populated->extraFields);

        // Absent entirely.
        $absent = $this->cardholderDataFrom([]);
        $this->assertNull($absent->extraFields);
    }

    /**
     * One response encodes an ISO numeric code three ways: `currency_code` as an int,
     * `card_country`/`ip_country` as zero-padded strings, `cardholder_data.country` as an
     * int. Each must be preserved as sent rather than normalised.
     */
    public function testTheThreeIsoNumericEncodingsArePreservedAsSent(): void
    {
        $transaction = $this->detailedTransactionFrom([
            'currency_code' => 208,
            'card_country' => '004',
            'ip_country' => '208',
            'has_cardholder_data' => true,
            'cardholder_data' => ['country' => 4],
        ]);

        // int, not "208".
        $this->assertSame(208, $transaction->currencyCode);
        // Zero-padded strings, not 4 and 208 — the padding must survive.
        $this->assertSame('004', $transaction->cardCountry);
        $this->assertSame('208', $transaction->ipCountry);
        // int for the very same concept, and not zero-padded.
        $this->assertInstanceOf(CardholderData::class, $transaction->cardholderData);
        $this->assertSame(4, $transaction->cardholderData->country);
    }

    public function testCountryFieldsAreNullableOnTheDetailEndpoint(): void
    {
        $transaction = $this->detailedTransactionFrom([
            'card_country' => null,
            'ip_country' => null,
        ]);

        $this->assertNull($transaction->cardCountry);
        $this->assertNull($transaction->ipCountry);
    }

    /** Absent must read as null and a zero surcharge as 0, so the two stay distinguishable. */
    public function testAnAbsentFeeIsNullAndAZeroFeeIsZero(): void
    {
        $withoutFee = FixtureLoader::load('transaction/detailed');
        unset($withoutFee['data']['fee']);
        $this->assertNull($this->detailedFromPayload($withoutFee)->fee);

        $zeroFee = FixtureLoader::load('transaction/detailed');
        $zeroFee['data']['fee'] = 0;
        $this->assertSame(0, $this->detailedFromPayload($zeroFee)->fee);
    }

    /** Null unless merchant-initiated, with `links.subscription` omitted alongside. */
    public function testSubscriptionReferencesAreNullableOnATransaction(): void
    {
        $transaction = $this->detailedTransactionFrom([
            'subscription_number' => null,
            'subscription_uuid' => null,
        ]);

        $this->assertNull($transaction->subscriptionNumber);
        $this->assertNull($transaction->subscriptionUuid);
        $this->assertNotNull($transaction->links);
        $rels = array_map(static fn ($link): ?string => $link->rel, $transaction->links);
        // Guard against a vacuous assertion: the link set must actually have been read.
        $this->assertContains('self', $rels);
        $this->assertNotContains('subscription', $rels);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function detailedTransactionFrom(array $overrides): DetailedTransaction
    {
        $payload = FixtureLoader::load('transaction/detailed');
        foreach ($overrides as $key => $value) {
            $payload['data'][$key] = $value;
        }

        return $this->detailedFromPayload($payload);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function cardholderDataFrom(array $overrides): CardholderData
    {
        $transaction = $this->detailedTransactionFrom([
            'has_cardholder_data' => true,
            'cardholder_data' => $overrides,
        ]);

        $this->assertInstanceOf(CardholderData::class, $transaction->cardholderData);

        return $transaction->cardholderData;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function detailedFromPayload(array $payload): DetailedTransaction
    {
        $this->http->willReturnJson($payload, 200, 'GET');

        return $this->createApi()->transaction()->getTransaction('1001');
    }
}
