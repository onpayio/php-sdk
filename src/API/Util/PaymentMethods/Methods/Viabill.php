<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\PaymentMethods\Enums\Acquirers;
use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;

/**
 * @internal Internal use only
 */
final class Viabill extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::DKK, CurrencyCodes::EUR];
    const METHOD_NAME = Acquirers::VIABILL;
}
