<?php

declare(strict_types=1);

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Enum\PaymentMethod;
use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;

/**
 * @internal Internal use only
 */
final class PayPal extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::ALL_CURRENCY_CODES];
    /** @deprecated Use {@see PayPal::getMethod()} or {@see PaymentMethod::PAYPAL} instead. */
    const METHOD_NAME = PaymentMethod::PAYPAL->value;

    public function getMethod(): PaymentMethod {
        return PaymentMethod::PAYPAL;
    }
}
