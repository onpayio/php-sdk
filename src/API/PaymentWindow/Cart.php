<?php

namespace OnPay\API\PaymentWindow;

class Cart {
    /**
     * @var CartShipping|null
     */
    private $shipping = null;
    /**
     * @var CartHandling|null
     */
    private $handling = null;
    /**
     * @var int|null
     */
    private $discount = null;
    /**
     * @var CartItem[]
     */
    private $items = [];

    /**
     * Set the shipping costs
     *
     * @param int $price Amount in minor units, including tax and discount
     * @param int $tax Amount in minor monetary units
     * @param int|null $discount Amount in minor monetary units
     * @return void
     */
    public function setShipping($price, $tax, $discount = null) {
        $this->shipping = new CartShipping();
        $this->shipping->price = intval($price);
        $this->shipping->tax = intval($tax);
        if (null !== $discount) {
            $this->shipping->discount = intval($discount);
        }
    }

    /**
     * Set the handling fee
     *
     * @param int $price Amount in minor units, including tax
     * @param int $tax Amount in minor monetary units
     * @return void
     */
    public function setHandling($price, $tax) {
        $this->handling = new CartHandling();
        $this->handling->price = intval($price);
        $this->handling->tax = intval($tax);
    }

    /**
     * @param int $amount Amount in minor units
     * @return void
     */
    public function setDiscount($amount) {
        $this->discount = intval($amount);
    }

    public function addItem(CartItem $cartItem) {
        $this->items[] = $cartItem;
    }

    public function setItems(array $items) {
        $this->items = [];
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    /**
     * @return CartShipping|null
     */
    public function getShipping() {
        return $this->shipping;
    }

    /**
     * @return CartHandling|null
     */
    public function getHandling() {
        return $this->handling;
    }

    /**
     * @return int|null
     */
    public function getDiscount() {
        return $this->discount;
    }

    /**
     * @return CartItem[]
     */
    public function getItems() {
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
    public function getFields() {
        $output = [];
        if (null !== $this->shipping) {
            $output['onpay_cart_shipping_price'] = $this->shipping->price;
            $output['onpay_cart_shipping_tax'] = $this->shipping->tax;
            if (null !== $this->shipping->discount) {
                $output['onpay_cart_shipping_discount'] = $this->shipping->discount;
            }
        }
        if (null !== $this->handling) {
            $output['onpay_cart_handling_price'] = $this->handling->price;
            $output['onpay_cart_handling_tax'] = $this->handling->tax;
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
}
