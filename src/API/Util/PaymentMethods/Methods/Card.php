<?php

declare(strict_types=1);

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Enum\PaymentMethod;
use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;

/**
 * @internal Internal use only
 */
final class Card extends PaymentMethodAbstract {
    const CURRENCIES = [CurrencyCodes::ALL_CURRENCY_CODES];
    /** @deprecated Use {@see Card::getMethod()} or {@see PaymentMethod::CARD} instead. */
    const METHOD_NAME = PaymentMethod::CARD->value;

    public function getMethod(): PaymentMethod {
        return PaymentMethod::CARD;
    }
}
