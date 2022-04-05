<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\CurrencyCodes;
use OnPay\API\Util\PaymentMethods\Acquirers;

/**
 * @internal Internal use only
 */
final class Viabill extends AbstractMethods {
    const CURRENCIES = [CurrencyCodes::DKK, CurrencyCodes::EUR];
    const METHOD_NAME = Acquirers::VIABILL;
}
