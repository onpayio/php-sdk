<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\CurrencyCodes;
use OnPay\API\Util\PaymentMethods\Wallets;

/**
 * @internal Internal use only
 */
final class Googlepay extends AbstractMethods {
    const CURRENCIES = [CurrencyCodes::ALL_CURRENCY_CODES];
    const METHOD_NAME = Wallets::GOOGLE_PAY;
}
