<?php

namespace Tests\Unit\PaymentWindow;

use OnPay\API\Exception\InvalidCartException;
use OnPay\API\PaymentWindow\Cart;
use OnPay\API\PaymentWindow\CartHandling;
use OnPay\API\PaymentWindow\CartItem;
use OnPay\API\PaymentWindow\CartShipping;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    // --- Defaults / getters on a fresh cart -------------------------------

    public function testFreshCartHasNullDefaults(): void
    {
        $cart = new Cart();

        $this->assertNull($cart->getShipping());
        $this->assertNull($cart->getHandling());
        $this->assertNull($cart->getDiscount());
        $this->assertSame([], $cart->getItems());
        $this->assertSame([], $cart->getFields());
    }

    // --- setShipping ------------------------------------------------------

    public function testSetShippingWithoutDiscountOrName(): void
    {
        $cart = new Cart();
        // Strings pin the intval() coercion done in setShipping.
        $cart->setShipping('100', '25');

        $shipping = $cart->getShipping();
        $this->assertInstanceOf(CartShipping::class, $shipping);
        $this->assertSame(100, $shipping->price);
        $this->assertSame(25, $shipping->tax);
        $this->assertNull($shipping->discount);
        $this->assertNull($shipping->name);
    }

    public function testSetShippingWithDiscountAndName(): void
    {
        $cart = new Cart();
        $cart->setShipping('100', '25', '10', 'Express');

        $shipping = $cart->getShipping();
        $this->assertInstanceOf(CartShipping::class, $shipping);
        $this->assertSame(100, $shipping->price);
        $this->assertSame(25, $shipping->tax);
        $this->assertSame(10, $shipping->discount);
        $this->assertSame('Express', $shipping->name);
    }

    // --- setHandling ------------------------------------------------------

    public function testSetHandlingWithoutName(): void
    {
        $cart = new Cart();
        $cart->setHandling('30', '5');

        $handling = $cart->getHandling();
        $this->assertInstanceOf(CartHandling::class, $handling);
        $this->assertSame(30, $handling->price);
        $this->assertSame(5, $handling->tax);
        $this->assertNull($handling->name);
    }

    public function testSetHandlingWithName(): void
    {
        $cart = new Cart();
        $cart->setHandling('30', '5', 'Gift wrap');

        $handling = $cart->getHandling();
        $this->assertInstanceOf(CartHandling::class, $handling);
        $this->assertSame(30, $handling->price);
        $this->assertSame(5, $handling->tax);
        $this->assertSame('Gift wrap', $handling->name);
    }

    // --- setDiscount ------------------------------------------------------

    public function testSetDiscountCoercesToInt(): void
    {
        $cart = new Cart();
        $cart->setDiscount('42');

        $this->assertSame(42, $cart->getDiscount());
    }

    // --- addItem / setItems -----------------------------------------------

    public function testAddItemAppends(): void
    {
        $cart = new Cart();
        $a = new CartItem('A', 100, 1, 0);
        $b = new CartItem('B', 200, 1, 0);
        $cart->addItem($a);
        $cart->addItem($b);

        $this->assertSame([$a, $b], $cart->getItems());
    }

    public function testSetItemsResetsExistingItems(): void
    {
        $cart = new Cart();
        $a = new CartItem('A', 100, 1, 0);
        $b = new CartItem('B', 200, 1, 0);
        $cart->addItem($a);
        $cart->setItems([$b]);

        $this->assertSame([$b], $cart->getItems());
    }

    public function testSetItemsReindexesKeys(): void
    {
        $cart = new Cart();
        $a = new CartItem('A', 100, 1, 0);
        $b = new CartItem('B', 200, 1, 0);
        $cart->setItems(['x' => $a, 'y' => $b]);

        $items = $cart->getItems();
        $this->assertSame([0, 1], array_keys($items));
        $this->assertSame($a, $items[0]);
        $this->assertSame($b, $items[1]);
    }

    // --- getItems collapse logic ------------------------------------------

    public function testGetItemsReturnsUpToTwentyItemsUnchanged(): void
    {
        $cart = new Cart();
        $items = [];
        for ($i = 0; $i < 20; $i++) {
            $item = new CartItem('Item ' . $i, 100, 2, 10);
            $items[] = $item;
            $cart->addItem($item);
        }

        $result = $cart->getItems();
        $this->assertCount(20, $result);
        $this->assertSame($items, $result);
        // Boundary: 20 is not "> 20", so no collapsed item is added.
        $this->assertSame('Item 19', $result[19]->getName());
    }

    public function testGetItemsCollapsesLastItemsWhenMoreThanTwenty(): void
    {
        $cart = new Cart();
        $items = [];
        for ($i = 0; $i < 21; $i++) {
            $item = new CartItem('Item ' . $i, 100, 2, 10);
            $items[] = $item;
            $cart->addItem($item);
        }

        $result = $cart->getItems();
        // 19 original items are kept, plus one collapsed placeholder.
        $this->assertCount(20, $result);
        $this->assertSame($items[0], $result[0]);
        $this->assertSame($items[18], $result[18]);

        // The 20th and 21st items collapse into a single '...' item.
        $collapsed = $result[19];
        $this->assertInstanceOf(CartItem::class, $collapsed);
        $this->assertSame('...', $collapsed->getName());
        $this->assertSame(400, $collapsed->getPrice()); // (100*2) + (100*2)
        $this->assertSame(1, $collapsed->getQuantity());
        $this->assertSame(40, $collapsed->getTax()); // (10*2) + (10*2)
    }

    // --- getFields --------------------------------------------------------

    public function testGetFieldsWithFullCart(): void
    {
        $cart = new Cart();
        $cart->setShipping(50, 10, 5, 'Ship');
        $cart->setHandling(30, 5, 'Handle');
        $cart->setDiscount(20);
        $cart->addItem(new CartItem('Item A', 100, 2, 20));
        $cart->addItem(new CartItem(
            'Item B',
            200,
            1,
            40,
            'Desc',
            'SKU1',
            'pcs',
            '1234567890',
            CartItem::TYPE_PHYSICAL
        ));

        $this->assertSame([
            'onpay_cart_shipping_price' => 50,
            'onpay_cart_shipping_tax' => 10,
            'onpay_cart_shipping_discount' => 5,
            'onpay_cart_shipping_name' => 'Ship',
            'onpay_cart_handling_price' => 30,
            'onpay_cart_handling_tax' => 5,
            'onpay_cart_handling_name' => 'Handle',
            'onpay_cart_discount' => 20,
            'onpay_cart_items[0][name]' => 'Item A',
            'onpay_cart_items[0][price]' => 100,
            'onpay_cart_items[0][quantity]' => 2,
            'onpay_cart_items[0][tax]' => 20,
            'onpay_cart_items[1][name]' => 'Item B',
            'onpay_cart_items[1][description]' => 'Desc',
            'onpay_cart_items[1][sku]' => 'SKU1',
            'onpay_cart_items[1][price]' => 200,
            'onpay_cart_items[1][quantity]' => 1,
            'onpay_cart_items[1][tax]' => 40,
            'onpay_cart_items[1][quantity_unit]' => 'pcs',
            'onpay_cart_items[1][global_trade_item_number]' => '1234567890',
            'onpay_cart_items[1][type]' => 'physical',
        ], $cart->getFields());
    }

    public function testGetFieldsOmitsOptionalShippingAndHandlingFields(): void
    {
        $cart = new Cart();
        $cart->setShipping(50, 10); // no discount, no name
        $cart->setHandling(30, 5); // no name
        $cart->addItem(new CartItem('Item A', 100, 1, 0));

        $fields = $cart->getFields();

        $this->assertArrayNotHasKey('onpay_cart_shipping_discount', $fields);
        $this->assertArrayNotHasKey('onpay_cart_shipping_name', $fields);
        $this->assertArrayNotHasKey('onpay_cart_handling_name', $fields);
        $this->assertArrayNotHasKey('onpay_cart_discount', $fields);

        $this->assertSame([
            'onpay_cart_shipping_price' => 50,
            'onpay_cart_shipping_tax' => 10,
            'onpay_cart_handling_price' => 30,
            'onpay_cart_handling_tax' => 5,
            'onpay_cart_items[0][name]' => 'Item A',
            'onpay_cart_items[0][price]' => 100,
            'onpay_cart_items[0][quantity]' => 1,
            'onpay_cart_items[0][tax]' => 0,
        ], $fields);
    }

    public function testGetFieldsUsesCollapsedItemsWhenMoreThanTwenty(): void
    {
        $cart = new Cart();
        for ($i = 0; $i < 21; $i++) {
            $cart->addItem(new CartItem('Item ' . $i, 100, 2, 10));
        }

        $fields = $cart->getFields();

        // Items are re-indexed 0..19, with index 19 being the '...' placeholder.
        $this->assertArrayHasKey('onpay_cart_items[19][name]', $fields);
        $this->assertSame('...', $fields['onpay_cart_items[19][name]']);
        $this->assertSame(400, $fields['onpay_cart_items[19][price]']);
        $this->assertSame(1, $fields['onpay_cart_items[19][quantity]']);
        $this->assertSame(40, $fields['onpay_cart_items[19][tax]']);
        $this->assertArrayNotHasKey('onpay_cart_items[20][name]', $fields);
    }

    // --- throwOnInvalid: valid cart ---------------------------------------

    public function testThrowOnInvalidPassesForBalancedCart(): void
    {
        $cart = new Cart();
        $cart->addItem(new CartItem('Item', 100, 2, 20)); // itemTotal 200
        $cart->setShipping(50, 10, 10); // +50 -10 => +40
        $cart->setHandling(30, 5); // +30
        $cart->setDiscount(20); // -20
        // 200 + 40 + 30 - 20 = 250

        // '250' pins the intval() coercion of $amount. No exception expected.
        $cart->throwOnInvalid('250');

        $this->assertSame(20, $cart->getDiscount());
    }

    public function testThrowOnInvalidPassesForShippingWithoutDiscount(): void
    {
        $cart = new Cart();
        $cart->addItem(new CartItem('Item', 100, 1, 0)); // 100
        $cart->setShipping(50, 10); // +50, no discount branch
        $cart->setHandling(30, 5); // +30
        // total 180

        $cart->throwOnInvalid(180);

        // No exception thrown; the shipping discount branch stays null.
        $this->assertNull($cart->getShipping()->discount);
    }

    // --- throwOnInvalid: individual error branches ------------------------

    public function testThrowOnInvalidRejectsNegativeAmount(): void
    {
        $cart = new Cart();
        // Discount pulls cart total to -5 so the total still matches -5,
        // isolating the negative-amount error. asserts current behaviour.
        $cart->setDiscount(5);

        try {
            $cart->throwOnInvalid(-5);
            $this->fail('Expected InvalidCartException');
        } catch (InvalidCartException $e) {
            $this->assertContains('Amount cannot be negative', $e->errors);
        }
    }

    public function testThrowOnInvalidRejectsNegativeItemPrice(): void
    {
        $cart = new Cart();
        $cart->addItem(new CartItem('Bad', -100, 1, 0));

        try {
            $cart->throwOnInvalid(-100);
            $this->fail('Expected InvalidCartException');
        } catch (InvalidCartException $e) {
            $this->assertContains('Item price cannot be negative: Bad', $e->errors);
        }
    }

    public function testThrowOnInvalidRejectsNegativeItemTax(): void
    {
        $cart = new Cart();
        $cart->addItem(new CartItem('Bad', 100, 1, -10));

        try {
            $cart->throwOnInvalid(100);
            $this->fail('Expected InvalidCartException');
        } catch (InvalidCartException $e) {
            $this->assertSame(['Item tax cannot be negative: Bad'], $e->errors);
        }
    }

    public function testThrowOnInvalidRejectsNegativeItemQuantity(): void
    {
        $cart = new Cart();
        $cart->addItem(new CartItem('Bad', 100, -1, 0)); // itemTotal -100
        $cart->setShipping(100, 0); // +100 => cartTotal 0

        try {
            $cart->throwOnInvalid(0);
            $this->fail('Expected InvalidCartException');
        } catch (InvalidCartException $e) {
            $this->assertSame(['Item quantity cannot be negative: Bad'], $e->errors);
        }
    }

    public function testThrowOnInvalidRejectsItemTaxHigherThanPrice(): void
    {
        $cart = new Cart();
        $cart->addItem(new CartItem('Bad', 50, 1, 80));

        try {
            $cart->throwOnInvalid(50);
            $this->fail('Expected InvalidCartException');
        } catch (InvalidCartException $e) {
            $this->assertSame(['Tax value higher than price on: Bad'], $e->errors);
        }
    }

    public function testThrowOnInvalidRejectsNegativeShippingPrice(): void
    {
        $cart = new Cart();
        $cart->setShipping(-50, 0);

        try {
            $cart->throwOnInvalid(-50);
            $this->fail('Expected InvalidCartException');
        } catch (InvalidCartException $e) {
            $this->assertContains('Shipping price cannot be negative', $e->errors);
        }
    }

    public function testThrowOnInvalidRejectsNegativeShippingTax(): void
    {
        $cart = new Cart();
        $cart->setShipping(100, -10);

        try {
            $cart->throwOnInvalid(100);
            $this->fail('Expected InvalidCartException');
        } catch (InvalidCartException $e) {
            $this->assertSame(['Shipping tax cannot be negative'], $e->errors);
        }
    }

    public function testThrowOnInvalidRejectsShippingTaxHigherThanPrice(): void
    {
        $cart = new Cart();
        $cart->setShipping(50, 80);

        try {
            $cart->throwOnInvalid(50);
            $this->fail('Expected InvalidCartException');
        } catch (InvalidCartException $e) {
            $this->assertSame(['Tax on shipping higher than price'], $e->errors);
        }
    }

    public function testThrowOnInvalidRejectsNegativeShippingDiscount(): void
    {
        $cart = new Cart();
        $cart->setShipping(100, 0, -10); // cartTotal 100 - (-10) = 110

        try {
            $cart->throwOnInvalid(110);
            $this->fail('Expected InvalidCartException');
        } catch (InvalidCartException $e) {
            $this->assertSame(['Shipping discount cannot be negative'], $e->errors);
        }
    }

    public function testThrowOnInvalidRejectsShippingDiscountHigherThanPrice(): void
    {
        $cart = new Cart();
        $cart->addItem(new CartItem('Item', 50, 1, 0)); // +50
        $cart->setShipping(100, 0, 150); // +100 -150 => cartTotal 0

        try {
            $cart->throwOnInvalid(0);
            $this->fail('Expected InvalidCartException');
        } catch (InvalidCartException $e) {
            $this->assertSame(['Shipping discount higher than price'], $e->errors);
        }
    }

    public function testThrowOnInvalidRejectsNegativeHandlingPrice(): void
    {
        $cart = new Cart();
        $cart->setHandling(-30, 0);

        try {
            $cart->throwOnInvalid(-30);
            $this->fail('Expected InvalidCartException');
        } catch (InvalidCartException $e) {
            $this->assertContains('Handling price cannot be negative', $e->errors);
        }
    }

    public function testThrowOnInvalidRejectsNegativeHandlingTax(): void
    {
        $cart = new Cart();
        $cart->setHandling(100, -5);

        try {
            $cart->throwOnInvalid(100);
            $this->fail('Expected InvalidCartException');
        } catch (InvalidCartException $e) {
            $this->assertSame(['Handling tax cannot be negative'], $e->errors);
        }
    }

    public function testThrowOnInvalidRejectsHandlingTaxHigherThanPrice(): void
    {
        $cart = new Cart();
        $cart->setHandling(50, 80);

        try {
            $cart->throwOnInvalid(50);
            $this->fail('Expected InvalidCartException');
        } catch (InvalidCartException $e) {
            $this->assertSame(['Tax on handling higher than price'], $e->errors);
        }
    }

    public function testThrowOnInvalidRejectsNegativeDiscount(): void
    {
        $cart = new Cart();
        $cart->setDiscount(-20); // cartTotal 0 - (-20) = 20

        try {
            $cart->throwOnInvalid(20);
            $this->fail('Expected InvalidCartException');
        } catch (InvalidCartException $e) {
            $this->assertSame(['Discount cannot be negative'], $e->errors);
        }
    }

    public function testThrowOnInvalidRejectsMismatchedTotal(): void
    {
        $cart = new Cart();

        try {
            $cart->throwOnInvalid(5);
            $this->fail('Expected InvalidCartException');
        } catch (InvalidCartException $e) {
            $this->assertSame(
                ['Cart total does not match amount for payment, cart total was calculated to: 0, amount provided is: 5'],
                $e->errors
            );
        }
    }

    // --- InvalidCartException aggregation ---------------------------------

    public function testInvalidCartExceptionAggregatesErrorsAndMessage(): void
    {
        $cart = new Cart();

        try {
            $cart->throwOnInvalid(-5);
            $this->fail('Expected InvalidCartException');
        } catch (InvalidCartException $e) {
            $this->assertSame([
                'Amount cannot be negative',
                'Cart total does not match amount for payment, cart total was calculated to: 0, amount provided is: -5',
            ], $e->errors);
            $this->assertSame(
                '2 validation errors: Amount cannot be negative, Cart total does not match amount for payment, cart total was calculated to: 0, amount provided is: -5',
                $e->getMessage()
            );
        }
    }
}
