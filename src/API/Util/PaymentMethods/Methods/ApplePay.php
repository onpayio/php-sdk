<?php

declare(strict_types=1);

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Enum\PaymentMethod;
use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;

/**
 * @internal Internal use only
 */
final class ApplePay extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::ALL_CURRENCY_CODES];
    /** @deprecated Use {@see ApplePay::getMethod()} or {@see PaymentMethod::APPLE_PAY} instead. */
    const METHOD_NAME = PaymentMethod::APPLE_PAY->value;

    public function getMethod(): PaymentMethod {
        return PaymentMethod::APPLE_PAY;
    }
}
