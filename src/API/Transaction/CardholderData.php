<?php

namespace OnPay\API\Transaction;


class CardholderData {
    /**
     * @internal Shall not be used outside the library
     * CardholderData constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->firstName = isset($data['first_name']) ? $data['first_name'] :  null;
        $this->lastName = isset($data['last_name']) ? $data['last_name'] :  null;
        $this->attention = isset($data['attention']) ? $data['attention'] :  null;
        $this->company = isset($data['company']) ? $data['company'] :  null;
        $this->street = isset($data['street']) ? $data['street'] :  null;
        $this->number = isset($data['number']) ? $data['number'] :  null;
        $this->floor = isset($data['floor']) ? $data['floor'] :  null;
        $this->door = isset($data['door']) ? $data['door'] :  null;
        $this->postalCode = isset($data['postal_code']) ? $data['postal_code'] :  null;
        $this->country = isset($data['country']) ? intval($data['country']) :  null;

        $this->email = isset($data['email']) ? $data['email'] :  null;
        $this->phone = isset($data['phone']) ? $data['phone'] :  null;

        if (isset($data['delivery_address'])) {
            $this->deliveryFirstName = isset($data['delivery_address']['first_name']) ? $data['delivery_address']['first_name'] :  null;
            $this->deliveryLastName = isset($data['delivery_address']['last_name']) ? $data['delivery_address']['last_name'] :  null;
            $this->deliveryAttention = isset($data['delivery_address']['attention']) ? $data['delivery_address']['attention'] :  null;
            $this->deliveryCompany = isset($data['delivery_address']['company']) ? $data['delivery_address']['company'] :  null;
            $this->deliveryStreet = isset($data['delivery_address']['street']) ? $data['delivery_address']['street'] :  null;
            $this->deliveryNumber = isset($data['delivery_address']['number']) ? $data['delivery_address']['number'] :  null;
            $this->deliveryFloor = isset($data['delivery_address']['floor']) ? $data['delivery_address']['floor'] :  null;
            $this->deliveryDoor = isset($data['delivery_address']['door']) ? $data['delivery_address']['door'] :  null;
            $this->deliveryPostalCode = isset($data['delivery_address']['postal_code']) ? $data['delivery_address']['postal_code'] :  null;
            $this->deliveryCountry = isset($data['delivery_address']['country']) ? intval($data['delivery_address']['country']) :  null;
        }

        $this->extraFields = isset($data['extra']) ? $data['extra'] : null;
    }

    /**
     * @var string|null
     */
    public $firstName = null;

    /**
     * @var string|null
     */
    public $lastName = null;

    /**
     * @var string|null
     */
    public $attention = null;

    /**
     * @var string|null
     */
    public $company = null;

    /**
     * @var string|null
     */
    public $street = null;

    /**
     * @var string|null
     */
    public $number = null;

    /**
     * @var string|null
     */
    public $floor = null;

    /**
     * @var string|null
     */
    public $door = null;

    /**
     * @var string|null
     */
    public $postalCode = null;

    /**
     * @var int|null
     */
    public $country = null;

    /**
     * @var string|null
     */
    public $email = null;

    /**
     * @var string|null
     */
    public $phone = null;

    /**
     * @var string|null
     */
    public $deliveryFirstName = null;

    /**
     * @var string|null
     */
    public $deliveryLastName = null;

    /**
     * @var string|null
     */
    public $deliveryAttention = null;

    /**
     * @var string|null
     */
    public $deliveryCompany = null;

    /**
     * @var string|null
     */
    public $deliveryStreet = null;

    /**
     * @var string|null
     */
    public $deliveryNumber = null;

    /**
     * @var string|null
     */
    public $deliveryFloor = null;

    /**
     * @var string|null
     */
    public $deliveryDoor = null;

    /**
     * @var string|null
     */
    public $deliveryPostalCode = null;

    /**
     * @var int|null
     */
    public $deliveryCountry = null;

    /**
     * @var array|null
     */
    public $extraFields = null;
}
