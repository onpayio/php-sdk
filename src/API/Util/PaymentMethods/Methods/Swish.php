<?php

declare(strict_types=1);

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Enum\PaymentMethod;
use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;

/**
 * @internal Internal use only
 */
final class Swish extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::SEK];
    /** @deprecated Use {@see Swish::getMethod()} or {@see PaymentMethod::SWISH} instead. */
    const METHOD_NAME = PaymentMethod::SWISH->value;

    public function getMethod(): PaymentMethod {
        return PaymentMethod::SWISH;
    }
}
