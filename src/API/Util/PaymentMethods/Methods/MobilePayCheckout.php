<?php

declare(strict_types=1);

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Enum\PaymentMethod;
use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;

/**
 * @internal Internal use only
 */
final class MobilePayCheckout extends PaymentMethodAbstract {
    const CURRENCIES = [
        CurrencyCodes::DKK,
        CurrencyCodes::NOK,
        CurrencyCodes::SEK,
        CurrencyCodes::GBP,
        CurrencyCodes::USD,
        CurrencyCodes::EUR,
    ];
    /** @deprecated Use {@see MobilePayCheckout::getMethod()} or {@see PaymentMethod::MOBILEPAY_CHECKOUT} instead. */
    const METHOD_NAME = PaymentMethod::MOBILEPAY_CHECKOUT->value;

    public function getMethod(): PaymentMethod {
        return PaymentMethod::MOBILEPAY_CHECKOUT;
    }
}
