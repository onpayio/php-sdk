<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\Currency;

interface PaymentMethodInterface {

    public function isAvailableForCurrency(Currency $currency): bool;

    /**
     * @return Currency[]
     */
    public function getCurrencies(): array;

    public function getName(): string;
}
