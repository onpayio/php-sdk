<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Util\CurrencyCodes;
use OnPay\API\Util\PaymentMethods\Enums\Methods;

/**
 * @internal Internal use only
 */
final class Card extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::ALL_CURRENCY_CODES];
    const METHOD_NAME = Methods::CARD;
}
