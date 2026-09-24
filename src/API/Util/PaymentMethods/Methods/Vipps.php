<?php

declare(strict_types=1);

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Enum\PaymentMethod;
use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;

/**
 * @internal Internal use only
 */
final class Vipps extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::NOK];
    /** @deprecated Use {@see Vipps::getMethod()} or {@see PaymentMethod::VIPPS} instead. */
    const METHOD_NAME = PaymentMethod::VIPPS->value;

    public function getMethod(): PaymentMethod {
        return PaymentMethod::VIPPS;
    }
}
