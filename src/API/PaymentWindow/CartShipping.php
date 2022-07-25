<?php

namespace OnPay\API\PaymentWindow;

/**
 * @internal Use the methods on the Cart class instead
 */
class CartShipping {
    /** @var int */
    public $price;
    /** @var int|null */
    public $discount = null;
    /** @var int */
    public $tax;
}
