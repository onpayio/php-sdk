<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\CurrencyCodes;
use OnPay\API\Util\PaymentMethods\Wallets;

/**
 * @internal Internal use only
 */
final class Mobilepay extends AbstractMethods {
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
