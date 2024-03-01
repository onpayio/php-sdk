<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;
use OnPay\API\Util\PaymentMethods\Enums\Methods;

/**
 * @internal Internal use only
 */
final class Klarna extends PaymentMethodAbstract {
    const CURRENCIES = [
        CurrencyCodes::AUD,
        CurrencyCodes::CAD,
        CurrencyCodes::CZK,
        CurrencyCodes::DKK,
        CurrencyCodes::NOK,
        CurrencyCodes::SEK,
        CurrencyCodes::CHF,
        CurrencyCodes::GBP,
        CurrencyCodes::USD,
        CurrencyCodes::EUR,
        CurrencyCodes::PLN,
    ];
    const METHOD_NAME = Methods::KLARNA;
}
