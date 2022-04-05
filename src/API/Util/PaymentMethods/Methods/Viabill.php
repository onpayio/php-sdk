<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\CurrencyCodes;
use OnPay\API\Util\PaymentMethods\Enums\Acquirers;

/**
 * @internal Internal use only
 */
final class Viabill extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::DKK, CurrencyCodes::EUR];
    const METHOD_NAME = Acquirers::VIABILL;
}
