<?php

declare(strict_types=1);

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Enum\PaymentMethod;
use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;

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
    /** @deprecated Use {@see Klarna::getMethod()} or {@see PaymentMethod::KLARNA} instead. */
    const METHOD_NAME = PaymentMethod::KLARNA->value;

    public function getMethod(): PaymentMethod {
        return PaymentMethod::KLARNA;
    }
}
