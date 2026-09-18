<?php

namespace Tests\Unit\Transaction;

use OnPay\API\Transaction\CardholderData;
use PHPUnit\Framework\TestCase;

/**
 * Direct value-object coverage for {@see CardholderData}, pinning both sides of the
 * delivery-address guard and the `extra` read.
 *
 * The stock detailed-with-cardholder fixture always carries a populated delivery_address
 * and a populated extra object, so the guarded branch and the extra read only ever take
 * their true side there. A billing-only payload (no delivery_address, no extra) is the
 * faithful shape the API emits when no delivery address / extra data exists, and it is
 * what proves the false side of both.
 */
class CardholderDataTest extends TestCase
{
    public function testBillingOnlyLeavesDeliveryAndExtraUnset(): void
    {
        // No `delivery_address` key and no `extra` key: the false side of both branches.
        $cardholder = new CardholderData([
            'first_name' => 'Jens',
            'last_name' => 'Hansen',
            'address1' => 'Hovedgaden 1',
            'postal_code' => '1000',
            'city' => 'Koebenhavn',
            'country' => '208',
            'email' => 'jens@example.test',
            'phone' => '+4512345678',
        ]);

        // Billing side reads through, and country is intval()-cast from its string.
        $this->assertSame('Jens', $cardholder->firstName);
        $this->assertSame('Hansen', $cardholder->lastName);
        $this->assertSame(208, $cardholder->country);

        // delivery_address absent -> the whole guarded block is skipped -> every delivery
        // field keeps its null default.
        $this->assertNull($cardholder->deliveryFirstName);
        $this->assertNull($cardholder->deliveryLastName);
        $this->assertNull($cardholder->deliveryAddress1);
        $this->assertNull($cardholder->deliveryPostalCode);
        $this->assertNull($cardholder->deliveryCity);
        $this->assertNull($cardholder->deliveryCountry);

        // extra absent -> extraFields null (the false side of the `extra` read).
        $this->assertNull($cardholder->extraFields);
    }

    public function testPopulatedDeliveryAndExtraAreRead(): void
    {
        // Distinct billing vs delivery country codes so the two intval() reads cannot be
        // confused with one another.
        $cardholder = new CardholderData([
            'first_name' => 'Jens',
            'country' => '208',
            'delivery_address' => [
                'first_name' => 'Mette',
                'last_name' => 'Nielsen',
                'address1' => 'Nyvej 2',
                'postal_code' => '2000',
                'city' => 'Frederiksberg',
                'country' => '752',
            ],
            'extra' => [
                'custom_reference' => 'ref-123',
                'loyalty_tier' => 'gold',
            ],
        ]);

        $this->assertSame(208, $cardholder->country);

        // delivery_address present -> the guarded block runs and each field is read.
        $this->assertSame('Mette', $cardholder->deliveryFirstName);
        $this->assertSame('Nielsen', $cardholder->deliveryLastName);
        $this->assertSame('Nyvej 2', $cardholder->deliveryAddress1);
        $this->assertSame('2000', $cardholder->deliveryPostalCode);
        $this->assertSame('Frederiksberg', $cardholder->deliveryCity);
        $this->assertSame(752, $cardholder->deliveryCountry);

        // extra present -> exposed verbatim as an array.
        $this->assertSame(
            ['custom_reference' => 'ref-123', 'loyalty_tier' => 'gold'],
            $cardholder->extraFields
        );
    }
}
