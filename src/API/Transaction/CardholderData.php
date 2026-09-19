<?php

declare(strict_types=1);

namespace OnPay\API\Transaction;

use OnPay\API\Util\DataReader;

class CardholderData {
    /**
     * @internal Shall not be used outside the library
     * CardholderData constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->firstName = DataReader::stringOrNull($data, 'first_name');
        $this->lastName = DataReader::stringOrNull($data, 'last_name');
        $this->attention = DataReader::stringOrNull($data, 'attention');
        $this->company = DataReader::stringOrNull($data, 'company');
        $this->address1 = DataReader::stringOrNull($data, 'address1');
        $this->address2 = DataReader::stringOrNull($data, 'address2');
        /** @psalm-suppress DeprecatedProperty deprecated field still populated for BC */
        $this->street = DataReader::stringOrNull($data, 'street');
        /** @psalm-suppress DeprecatedProperty deprecated field still populated for BC */
        $this->number = DataReader::stringOrNull($data, 'number');
        /** @psalm-suppress DeprecatedProperty deprecated field still populated for BC */
        $this->floor = DataReader::stringOrNull($data, 'floor');
        /** @psalm-suppress DeprecatedProperty deprecated field still populated for BC */
        $this->door = DataReader::stringOrNull($data, 'door');
        $this->postalCode = DataReader::stringOrNull($data, 'postal_code');
        $this->city = DataReader::stringOrNull($data, 'city');
        $this->country = isset($data['country']) ? intval($data['country']) : null;

        $this->email = DataReader::stringOrNull($data, 'email');
        $this->phone = DataReader::stringOrNull($data, 'phone');

        $deliveryAddress = DataReader::arrayOrNull($data, 'delivery_address');
        if ($deliveryAddress !== null) {
            $this->deliveryFirstName = DataReader::stringOrNull($deliveryAddress, 'first_name');
            $this->deliveryLastName = DataReader::stringOrNull($deliveryAddress, 'last_name');
            $this->deliveryAttention = DataReader::stringOrNull($deliveryAddress, 'attention');
            $this->deliveryCompany = DataReader::stringOrNull($deliveryAddress, 'company');
            $this->deliveryAddress1 = DataReader::stringOrNull($deliveryAddress, 'address1');
            $this->deliveryAddress2 = DataReader::stringOrNull($deliveryAddress, 'address2');
            /** @psalm-suppress DeprecatedProperty deprecated field still populated for BC */
            $this->deliveryStreet = DataReader::stringOrNull($deliveryAddress, 'street');
            /** @psalm-suppress DeprecatedProperty deprecated field still populated for BC */
            $this->deliveryNumber = DataReader::stringOrNull($deliveryAddress, 'number');
            /** @psalm-suppress DeprecatedProperty deprecated field still populated for BC */
            $this->deliveryFloor = DataReader::stringOrNull($deliveryAddress, 'floor');
            /** @psalm-suppress DeprecatedProperty deprecated field still populated for BC */
            $this->deliveryDoor = DataReader::stringOrNull($deliveryAddress, 'door');
            $this->deliveryPostalCode = DataReader::stringOrNull($deliveryAddress, 'postal_code');
            $this->deliveryCity = DataReader::stringOrNull($deliveryAddress, 'city');
            $this->deliveryCountry = isset($deliveryAddress['country']) ? intval($deliveryAddress['country']) : null;
        }

        $this->extraFields = DataReader::arrayOrNull($data, 'extra');
    }

    public ?string $firstName = null;

    public ?string $lastName = null;

    public ?string $attention = null;

    public ?string $company = null;

    public ?string $address1 = null;

    public ?string $address2 = null;

    /**
     * @deprecated Use $this->address1 and $this->address2 instead!
     */
    public ?string $street = null;

    /**
     * @deprecated Use $this->address1 and $this->address2 instead!
     */
    public ?string $number = null;

    /**
     * @deprecated Use $this->address1 and $this->address2 instead!
     */
    public ?string $floor = null;

    /**
     * @deprecated Use $this->address1 and $this->address2 instead!
     */
    public ?string $door = null;

    public ?string $postalCode = null;

    public ?string $city = null;

    public ?int $country = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $deliveryFirstName = null;

    public ?string $deliveryLastName = null;

    public ?string $deliveryAttention = null;

    public ?string $deliveryCompany = null;

    public ?string $deliveryAddress1 = null;

    public ?string $deliveryAddress2 = null;

    /**
     * @deprecated Use $this->deliveryAddress1 and $this->deliveryAddress2 instead!
     */
    public ?string $deliveryStreet = null;

    /**
     * @deprecated Use $this->deliveryAddress1 and $this->deliveryAddress2 instead!
     */
    public ?string $deliveryNumber = null;

    /**
     * @deprecated Use $this->deliveryAddress1 and $this->deliveryAddress2 instead!
     */
    public ?string $deliveryFloor = null;

    /**
     * @deprecated Use $this->deliveryAddress1 and $this->deliveryAddress2 instead!
     */
    public ?string $deliveryDoor = null;

    public ?string $deliveryPostalCode = null;

    public ?string $deliveryCity = null;

    public ?int $deliveryCountry = null;

    /**
     * @var array<array-key, mixed>|null
     */
    public ?array $extraFields = null;
}
