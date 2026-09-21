<?php

declare(strict_types=1);

namespace OnPay\API\PaymentWindow;

use OnPay\API\Exception\InvalidCartException;

final class Cart {
    private ?CartShipping $shipping = null;
    private ?CartHandling $handling = null;
    private ?int $discount = null;
    /**
     * @var CartItem[]
     */
    private array $items = [];

    /**
     * Set the shipping costs
     *
     * @param int $price Amount in minor units, including tax and discount
     * @param int $tax Amount in minor monetary units
     * @param int|null $discount Amount in minor monetary units
     * @param string|null $name Name that applies to the shipping
     * @return void
     */
    public function setShipping($price, $tax, $discount = null, ?string $name = null): void {
        $this->shipping = new CartShipping();
        $this->shipping->price = intval($price);
        $this->shipping->tax = intval($tax);
        if (null !== $discount) {
            $this->shipping->discount = intval($discount);
        }
        if (null !== $name) {
            $this->shipping->name = $name;
        }
    }

    /**
     * Set the handling fee
     *
     * @param int $price Amount in minor units, including tax
     * @param int $tax Amount in minor monetary units
     * @param string|null $name Name that applies to the handling
     * @return void
     */
    public function setHandling($price, $tax, ?string $name = null): void {
        $this->handling = new CartHandling();
        $this->handling->price = intval($price);
        $this->handling->tax = intval($tax);
        if (null !== $name) {
            $this->handling->name = $name;
        }
    }

    /**
     * @param int $amount Amount in minor units
     * @return void
     */
    public function setDiscount($amount): void {
        $this->discount = intval($amount);
    }

    public function addItem(CartItem $cartItem): void {
        $this->items[] = $cartItem;
    }

    /**
     * @param CartItem[] $items
     */
    public function setItems(array $items): void {
        $this->items = [];
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    /**
     * @return CartShipping|null
     */
    public function getShipping(): ?CartShipping {
        return $this->shipping;
    }

    /**
     * @return CartHandling|null
     */
    public function getHandling(): ?CartHandling {
        return $this->handling;
    }

    /**
     * @return int|null
     */
    public function getDiscount(): ?int {
        return $this->discount;
    }

    /**
     * @return CartItem[]
     */
    public function getItems(): array {
        if (count($this->items) > 20) {
            // We will collapse the last one in this case
            $output = [];
            $i = 0;
            $lastItem = [
                'price' => 0,
                'tax' => 0,
            ];
            foreach ($this->items as $item) {
                $i++;
                if ($i >= 20) {
                    $lastItem['price'] += ($item->getPrice() * $item->getQuantity());
                    $lastItem['tax'] += ($item->getTax() * $item->getQuantity());
                    continue;
                }
                $output[] = $item;
            }
            $output[] = new CartItem('...', $lastItem['price'], 1, $lastItem['tax']);

            return $output;
        }

        return $this->items;
    }

    /**
     * @internal
     * @return array
     */
    public function getFields(): array {
        $output = [];
        if (null !== $this->shipping) {
            $output['onpay_cart_shipping_price'] = $this->shipping->price;
            $output['onpay_cart_shipping_tax'] = $this->shipping->tax;
            if (null !== $this->shipping->discount) {
                $output['onpay_cart_shipping_discount'] = $this->shipping->discount;
            }
            if (null !== $this->shipping->name) {
                $output['onpay_cart_shipping_name'] = $this->shipping->name;
            }
        }
        if (null !== $this->handling) {
            $output['onpay_cart_handling_price'] = $this->handling->price;
            $output['onpay_cart_handling_tax'] = $this->handling->tax;
            if (null !== $this->handling->name) {
                $output['onpay_cart_handling_name'] = $this->handling->name;
            }
        }
        if (null !== $this->discount) {
            $output['onpay_cart_discount'] = $this->discount;
        }

        $i = 0;
        foreach ($this->getItems() as $item) {
            $fields = $item->getFields();
            foreach ($fields as $key => $value) {
                $output['onpay_cart_items[' . $i . '][' . $key . ']'] = $value;
            }
            $i++;
        }

        return $output;
    }

    /**
     * @param int|string|null $amount
     * @return void
     * @throws InvalidCartException
     * @internal
     */
    public function throwOnInvalid($amount): void {
        $errors = [];

        $amount = intval($amount);

        if ($amount < 0) {
            $errors[] = 'Amount cannot be negative';
        }

        $itemTotal = 0;
        $cartTotal = 0;

        foreach ($this->items as $item) {
            // Check for negative values
            if ($item->getPrice() < 0) {
                $errors[] = 'Item price cannot be negative: ' . $item->getName();
            }
            if ($item->getTax() < 0) {
                $errors[] = 'Item tax cannot be negative: ' . $item->getName();
            }
            if ($item->getQuantity() < 0) {
                $errors[] = 'Item quantity cannot be negative: ' . $item->getName();
            }
            // Check that tax is not larger than price
            if ($item->getTax() > $item->getPrice()) {
                $errors[] = 'Tax value higher than price on: ' . $item->getName();
            }
            $itemTotal += $item->getPrice() * $item->getQuantity();
        }
        $cartTotal += $itemTotal;

        if (null !== $this->shipping) {
            // Check for negative values
            if ($this->shipping->price < 0) {
                $errors[] = 'Shipping price cannot be negative';
            }
            if ($this->shipping->tax < 0) {
                $errors[] = 'Shipping tax cannot be negative';
            }
            // Check values in relation to each other
            if ($this->shipping->tax > $this->shipping->price) {
                $errors[] = 'Tax on shipping higher than price';
            }
            if (null !== $this->shipping->discount) {
                if ($this->shipping->discount < 0) {
                    $errors[] = 'Shipping discount cannot be negative';
                }
                if($this->shipping->discount > $this->shipping->price) {
                    $errors[] = 'Shipping discount higher than price';
                }
            }
            $cartTotal += $this->shipping->price;
            if (null !== $this->shipping->discount) {
                $cartTotal = $cartTotal - $this->shipping->discount;
            }
        }

        if (null !== $this->handling) {
            // Check for negative values
            if ($this->handling->price < 0) {
                $errors[] = 'Handling price cannot be negative';
            }
            if ($this->handling->tax < 0) {
                $errors[] = 'Handling tax cannot be negative';
            }
            // Check values in relation to each other
            if ($this->handling->tax > $this->handling->price) {
                $errors[] = 'Tax on handling higher than price';
            }
            $cartTotal += $this->handling->price;
        }

        if (null !== $this->discount) {
            if ($this->discount < 0) {
                $errors[] = 'Discount cannot be negative';
            }
            $cartTotal = $cartTotal - $this->discount;
        }

        if ($amount !== $cartTotal) {
            $errors[] = 'Cart total does not match amount for payment, cart total was calculated to: ' . $cartTotal . ', amount provided is: ' . $amount;
        }

        if (count($errors) > 0) {
            throw new InvalidCartException($errors);
        }
    }
}
