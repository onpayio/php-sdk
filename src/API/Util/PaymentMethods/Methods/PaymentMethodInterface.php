<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\Currency;

interface PaymentMethodInterface {

    public function isAvailableForCurrency(Currency $currency);

    public function getCurrencies();

    public function getName();
}
