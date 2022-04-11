<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\PaymentMethods\Enums\Acquirers;
use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;

/**
 * @internal Internal use only
 */
final class AnyDay extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::DKK];
    const METHOD_NAME = Acquirers::ANYDAY;
}
