<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\CurrencyCodes;
use OnPay\API\Util\PaymentMethods\Enums\Acquirers;

/**
 * @internal Internal use only
 */
final class Swish extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::SEK];
    const METHOD_NAME = Acquirers::SWISH;
}
