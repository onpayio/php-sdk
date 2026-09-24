<?php

declare(strict_types=1);

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Enum\PaymentMethod;
use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;

/**
 * @internal Internal use only
 */
final class GooglePay extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::ALL_CURRENCY_CODES];
    /** @deprecated Use {@see GooglePay::getMethod()} or {@see PaymentMethod::GOOGLE_PAY} instead. */
    const METHOD_NAME = PaymentMethod::GOOGLE_PAY->value;

    public function getMethod(): PaymentMethod {
        return PaymentMethod::GOOGLE_PAY;
    }
}
