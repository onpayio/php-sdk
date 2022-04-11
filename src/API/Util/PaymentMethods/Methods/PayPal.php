<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\PaymentMethods\Enums\Acquirers;
use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;

/**
 * @internal Internal use only
 */
final class PayPal extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::ALL_CURRENCY_CODES];
    const METHOD_NAME = Acquirers::PAYPAL;
}
