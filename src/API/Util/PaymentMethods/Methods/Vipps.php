<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\CurrencyCodes;
use OnPay\API\Util\PaymentMethods\Enums\Wallets;

/**
 * @internal Internal use only
 */
final class Vipps extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::NOK];
    const METHOD_NAME = Wallets::VIPPS;
}
