<?php

namespace OnPay\API\PaymentWindow;

class CartItem {
    /** @var string */
    private $name;
    /** @var string|null */
    private $description = null;
    /** @var string|null */
    private $sku = null;
    /** @var int */
    private $price;
    /** @var int */
    private $quantity;
    /** @var int */
    private $tax;

    /**
     * @param string $name 1-127 bytes
     * @param int $price Per item price including tax
     * @param int $quantity
     * @param int $tax
     * @param string|null $description 1-127 bytes
     * @param string|null $sku 1-127 bytes
     */
    public function __construct($name, $price, $quantity, $tax, $description = null, $sku = null) {
        $this->name = $name;
        $this->description = $description;
        $this->sku = $sku;
        $this->price = $price;
        $this->quantity = $quantity;
        $this->tax = $tax;
    }

    /**
     * @internal
     * @return array
     */
    public function getFields() {
        $output = [];
        $output['name'] = $this->name;
        if (null !== $this->description) {
            $output['description'] = $this->description;
        }
        if (null !== $this->sku) {
            $output['sku'] = $this->sku;
        }
        $output['price'] = $this->price;
        $output['quantity'] = $this->quantity;
        $output['tax'] = $this->tax;

        return $output;
    }

    /**
     * @return int
     */
    public function getPrice() {
        return $this->price;
    }

    /**
     * @return int
     */
    public function getQuantity() {
        return $this->quantity;
    }

    /**
     * @return int
     */
    public function getTax() {
        return $this->tax;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }
}
