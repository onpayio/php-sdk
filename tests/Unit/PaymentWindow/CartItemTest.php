<?php

namespace Tests\Unit\PaymentWindow;

use OnPay\API\PaymentWindow\CartItem;
use PHPUnit\Framework\TestCase;

class CartItemTest extends TestCase
{
    public function testConstantValues(): void
    {
        $this->assertSame('physical', CartItem::TYPE_PHYSICAL);
        $this->assertSame('virtual', CartItem::TYPE_VIRTUAL);
        $this->assertSame('giftcard', CartItem::TYPE_GIFTCARD);
    }

    public function testGetFieldsWithRequiredArgumentsOnly(): void
    {
        $item = new CartItem('Widget', 100, 2, 20);

        // assertSame pins both values and key order.
        $this->assertSame([
            'name' => 'Widget',
            'price' => 100,
            'quantity' => 2,
            'tax' => 20,
        ], $item->getFields());
    }

    public function testGetFieldsWithAllArguments(): void
    {
        $item = new CartItem(
            'Widget',
            100,
            2,
            20,
            'A useful widget',
            'SKU-1',
            'pcs',
            '1234567890123',
            CartItem::TYPE_PHYSICAL
        );

        $this->assertSame([
            'name' => 'Widget',
            'description' => 'A useful widget',
            'sku' => 'SKU-1',
            'price' => 100,
            'quantity' => 2,
            'tax' => 20,
            'quantity_unit' => 'pcs',
            'global_trade_item_number' => '1234567890123',
            'type' => 'physical',
        ], $item->getFields());
    }

    public function testGettersWithRequiredArgumentsOnly(): void
    {
        $item = new CartItem('Widget', 100, 2, 20);

        $this->assertSame('Widget', $item->getName());
        $this->assertSame(100, $item->getPrice());
        $this->assertSame(2, $item->getQuantity());
        $this->assertSame(20, $item->getTax());
        $this->assertNull($item->getDescription());
        $this->assertNull($item->getSku());
        $this->assertNull($item->getQuantityUnit());
        $this->assertNull($item->getGlobalTradeItemNumber());
        $this->assertNull($item->getType());
    }

    public function testGettersWithAllArguments(): void
    {
        $item = new CartItem(
            'Widget',
            100,
            2,
            20,
            'A useful widget',
            'SKU-1',
            'pcs',
            '1234567890123',
            CartItem::TYPE_GIFTCARD
        );

        $this->assertSame('Widget', $item->getName());
        $this->assertSame(100, $item->getPrice());
        $this->assertSame(2, $item->getQuantity());
        $this->assertSame(20, $item->getTax());
        $this->assertSame('A useful widget', $item->getDescription());
        $this->assertSame('SKU-1', $item->getSku());
        $this->assertSame('pcs', $item->getQuantityUnit());
        $this->assertSame('1234567890123', $item->getGlobalTradeItemNumber());
        $this->assertSame('giftcard', $item->getType());
    }
}
