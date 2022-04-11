<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;
use OnPay\API\Util\PaymentMethods\Enums\Wallets;

/**
 * @internal Internal use only
 */
final class MobilePay extends PaymentMethodAbstract {
    const CURRENCIES = [
        CurrencyCodes::DKK,
        CurrencyCodes::NOK,
        CurrencyCodes::SEK,
        CurrencyCodes::GBP,
        CurrencyCodes::USD,
        CurrencyCodes::EUR,
    ];
    const METHOD_NAME = Wallets::MOBILEPAY;
}
