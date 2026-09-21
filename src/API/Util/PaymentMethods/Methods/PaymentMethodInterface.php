<?php

declare(strict_types=1);

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Enum\PaymentMethod;
use OnPay\API\Util\Currency;

interface PaymentMethodInterface {

    public function isAvailableForCurrency(Currency $currency): bool;

    /**
     * @return Currency[]
     */
    public function getCurrencies(): array;

    /**
     * The payment method this class represents.
     */
    public function getMethod(): PaymentMethod;

    /**
     * The method's raw identifier, i.e. {@see PaymentMethod::$value}.
     */
    public function getName(): string;
}
