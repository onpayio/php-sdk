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
 *
 * The remaining per-field reads are pinned by a fully populated payload (every billing
 * and delivery key present) against an empty payload and an empty delivery_address block
 * (every key absent), so both sides of every isset() are taken.
 */
class CardholderDataTest extends TestCase
{
    public function testAllFieldsAreReadWhenPresent(): void
    {
        $cardholder = new CardholderData([
            'first_name' => 'Jens',
            'last_name' => 'Hansen',
            'attention' => 'Reception',
            'company' => 'Hansen ApS',
            'address1' => 'Hovedgaden 1',
            'address2' => '2. sal',
            'street' => 'Hovedgaden',
            'number' => '1',
            'floor' => '2',
            'door' => 'th',
            'postal_code' => '1000',
            'city' => 'Koebenhavn',
            'country' => '208',
            'email' => 'jens@example.test',
            'phone' => '+4512345678',
            'delivery_address' => [
                'first_name' => 'Mette',
                'last_name' => 'Nielsen',
                'attention' => 'Varemodtagelse',
                'company' => 'Nielsen A/S',
                'address1' => 'Nyvej 2',
                'address2' => 'Bygning B',
                'street' => 'Nyvej',
                'number' => '2',
                'floor' => '1',
                'door' => 'tv',
                'postal_code' => '2000',
                'city' => 'Frederiksberg',
                'country' => '752',
            ],
            'extra' => ['custom_reference' => 'ref-123'],
        ]);

        $this->assertSame('Jens', $cardholder->firstName);
        $this->assertSame('Hansen', $cardholder->lastName);
        $this->assertSame('Reception', $cardholder->attention);
        $this->assertSame('Hansen ApS', $cardholder->company);
        $this->assertSame('Hovedgaden 1', $cardholder->address1);
        $this->assertSame('2. sal', $cardholder->address2);
        $this->assertSame('Hovedgaden', $cardholder->street);
        $this->assertSame('1', $cardholder->number);
        $this->assertSame('2', $cardholder->floor);
        $this->assertSame('th', $cardholder->door);
        $this->assertSame('1000', $cardholder->postalCode);
        $this->assertSame('Koebenhavn', $cardholder->city);
        $this->assertSame(208, $cardholder->country);
        $this->assertSame('jens@example.test', $cardholder->email);
        $this->assertSame('+4512345678', $cardholder->phone);

        $this->assertSame('Mette', $cardholder->deliveryFirstName);
        $this->assertSame('Nielsen', $cardholder->deliveryLastName);
        $this->assertSame('Varemodtagelse', $cardholder->deliveryAttention);
        $this->assertSame('Nielsen A/S', $cardholder->deliveryCompany);
        $this->assertSame('Nyvej 2', $cardholder->deliveryAddress1);
        $this->assertSame('Bygning B', $cardholder->deliveryAddress2);
        $this->assertSame('Nyvej', $cardholder->deliveryStreet);
        $this->assertSame('2', $cardholder->deliveryNumber);
        $this->assertSame('1', $cardholder->deliveryFloor);
        $this->assertSame('tv', $cardholder->deliveryDoor);
        $this->assertSame('2000', $cardholder->deliveryPostalCode);
        $this->assertSame('Frederiksberg', $cardholder->deliveryCity);
        $this->assertSame(752, $cardholder->deliveryCountry);

        $this->assertSame(['custom_reference' => 'ref-123'], $cardholder->extraFields);
    }

    public function testEmptyPayloadLeavesEveryFieldNull(): void
    {
        $cardholder = new CardholderData([]);

        $this->assertNull($cardholder->firstName);
        $this->assertNull($cardholder->lastName);
        $this->assertNull($cardholder->attention);
        $this->assertNull($cardholder->company);
        $this->assertNull($cardholder->address1);
        $this->assertNull($cardholder->address2);
        $this->assertNull($cardholder->street);
        $this->assertNull($cardholder->number);
        $this->assertNull($cardholder->floor);
        $this->assertNull($cardholder->door);
        $this->assertNull($cardholder->postalCode);
        $this->assertNull($cardholder->city);
        $this->assertNull($cardholder->country);
        $this->assertNull($cardholder->email);
        $this->assertNull($cardholder->phone);
        $this->assertNull($cardholder->extraFields);
    }

    public function testEmptyDeliveryAddressLeavesEveryDeliveryFieldNull(): void
    {
        // delivery_address present but without any keys: the guarded block runs and every
        // nested isset() takes its false side.
        $cardholder = new CardholderData(['delivery_address' => []]);

        $this->assertNull($cardholder->deliveryFirstName);
        $this->assertNull($cardholder->deliveryLastName);
        $this->assertNull($cardholder->deliveryAttention);
        $this->assertNull($cardholder->deliveryCompany);
        $this->assertNull($cardholder->deliveryAddress1);
        $this->assertNull($cardholder->deliveryAddress2);
        $this->assertNull($cardholder->deliveryStreet);
        $this->assertNull($cardholder->deliveryNumber);
        $this->assertNull($cardholder->deliveryFloor);
        $this->assertNull($cardholder->deliveryDoor);
        $this->assertNull($cardholder->deliveryPostalCode);
        $this->assertNull($cardholder->deliveryCity);
        $this->assertNull($cardholder->deliveryCountry);
    }

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
