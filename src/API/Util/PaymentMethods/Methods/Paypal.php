<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\CurrencyCodes;
use OnPay\API\Util\PaymentMethods\Acquirers;

/**
 * @internal Internal use only
 */
final class Paypal extends AbstractMethods {
    const CURRENCIES = [CurrencyCodes::ALL_CURRENCY_CODES];
    const METHOD_NAME = Acquirers::PAYPAL;
}
