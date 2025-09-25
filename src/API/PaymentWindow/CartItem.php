<?php

namespace OnPay\API\PaymentWindow;

class CartItem {
    const TYPE_PHYSICAL = 'physical';
    const TYPE_VIRTUAL = 'virtual';
    const TYPE_GIFTCARD = 'giftcard';

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
    /** @var string|null */
    private $quantity_unit;
    /** @var string|null */
    private $global_trade_item_number;
    /** @var string|null */
    private $type;

    /**
     * @param string $name 1-127 bytes
     * @param int $price Per item price including tax
     * @param int $quantity
     * @param int $tax
     * @param string|null $description 1-127 bytes
     * @param string|null $sku 1-127 bytes
     * @param string|null $quantity_unit 1-127 bytes
     * @param string|null $global_trade_item_number 1-50 bytes
     * @param string|null $type 1-127 bytes
     */
    public function __construct($name, $price, $quantity, $tax, $description = null, $sku = null, $quantity_unit = null, $global_trade_item_number = null, $type = null) {
        $this->name = $name;
        $this->description = $description;
        $this->sku = $sku;
        $this->price = $price;
        $this->quantity = $quantity;
        $this->tax = $tax;
        $this->quantity_unit = $quantity_unit;
        $this->global_trade_item_number = $global_trade_item_number;
        $this->type = $type;
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
        if (null !== $this->quantity_unit) {
            $output['quantity_unit'] = $this->quantity_unit;
        }
        if (null !== $this->global_trade_item_number) {
            $output['global_trade_item_number'] = $this->global_trade_item_number;
        }
        if (null !== $this->type) {
            $output['type'] = $this->type;
        }

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

    /**
     * @return string|null
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getSku() {
        return $this->sku;
    }

    /**
     * @return string|null
     */
    public function getQuantityUnit() {
        return $this->quantity_unit;
    }

    /**
     * @return string|null
     */
    public function getGlobalTradeItemNumber() {
        return $this->global_trade_item_number;
    }

    /**
     * @return string|null
     */
    public function getType() {
        return $this->type;
    }
}
