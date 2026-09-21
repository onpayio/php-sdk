<?php

declare(strict_types=1);

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Enum\PaymentMethod;
use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;

/**
 * @internal Internal use only
 */
final class ViaBill extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::DKK, CurrencyCodes::EUR];
    /** @deprecated Use {@see ViaBill::getMethod()} or {@see PaymentMethod::VIABILL} instead. */
    const METHOD_NAME = PaymentMethod::VIABILL->value;

    public function getMethod(): PaymentMethod {
        return PaymentMethod::VIABILL;
    }
}
