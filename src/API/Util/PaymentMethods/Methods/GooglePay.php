<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;
use OnPay\API\Util\PaymentMethods\Enums\Wallets;

/**
 * @internal Internal use only
 */
final class GooglePay extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::ALL_CURRENCY_CODES];
    const METHOD_NAME = Wallets::GOOGLE_PAY;
}
