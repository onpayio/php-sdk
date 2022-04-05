<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\PaymentMethods\Enums\Acquirers;
use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;

/**
 * @internal Internal use only
 */
final class Swish extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::SEK];
    const METHOD_NAME = Acquirers::SWISH;
}
