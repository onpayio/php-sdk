<?php

declare(strict_types=1);

namespace OnPay\API\PaymentWindow;

/**
 * @internal Use the methods on the Cart class instead
 */
final class CartHandling {
    public int $price = 0;
    public int $tax = 0;
    public ?string $name = null;
}
