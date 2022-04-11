<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;
use OnPay\API\Util\PaymentMethods\Enums\Methods;

/**
 * @internal Internal use only
 */
final class ViaBill extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::DKK, CurrencyCodes::EUR];
    const METHOD_NAME = Methods::VIABILL;
}
