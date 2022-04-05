<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\CurrencyCodes;
use OnPay\API\Util\PaymentMethods\Enums\Acquirers;

/**
 * @internal Internal use only
 */
final class Anyday extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::DKK];
    const METHOD_NAME = Acquirers::ANYDAY;
}
