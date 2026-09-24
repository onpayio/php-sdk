<?php

declare(strict_types=1);

namespace OnPay\API\PaymentWindow;

final class CartItem {
    const TYPE_PHYSICAL = 'physical';
    const TYPE_VIRTUAL = 'virtual';
    const TYPE_GIFTCARD = 'giftcard';

    private string $name;
    private ?string $description = null;
    private ?string $sku = null;
    private int $price;
    private int $quantity;
    private int $tax;
    private ?string $quantity_unit;
    private ?string $global_trade_item_number;
    private ?string $type;

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
    public function __construct(string $name, int $price, int $quantity, int $tax, ?string $description = null, ?string $sku = null, ?string $quantity_unit = null, ?string $global_trade_item_number = null, ?string $type = null) {
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
     * @return array<string, int|string>
     */
    public function getFields(): array {
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
    public function getPrice(): int {
        return $this->price;
    }

    /**
     * @return int
     */
    public function getQuantity(): int {
        return $this->quantity;
    }

    /**
     * @return int
     */
    public function getTax(): int {
        return $this->tax;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getSku(): ?string {
        return $this->sku;
    }

    /**
     * @return string|null
     */
    public function getQuantityUnit(): ?string {
        return $this->quantity_unit;
    }

    /**
     * @return string|null
     */
    public function getGlobalTradeItemNumber(): ?string {
        return $this->global_trade_item_number;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string {
        return $this->type;
    }
}
