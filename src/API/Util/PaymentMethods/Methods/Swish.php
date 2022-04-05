<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\CurrencyCodes;
use OnPay\API\Util\PaymentMethods\Acquirers;

/**
 * @internal Internal use only
 */
final class Swish extends AbstractMethods {
    const CURRENCIES = [CurrencyCodes::SEK];
    const METHOD_NAME = Acquirers::SWISH;
}
