<?php

declare(strict_types=1);

namespace OnPay\API\Util\PaymentMethods\Enums;

use OnPay\API\Enum\PaymentMethod;

/**
 * Payment method identifiers.
 *
 * @deprecated Use the {@see PaymentMethod} enum instead. Every constant here is defined in
 *             terms of that enum and keeps its current value; the class is kept only so
 *             existing `Methods::CARD` references keep compiling and will be removed in a
 *             future major release.
 */
final class Methods {
    /** @deprecated Use {@see PaymentMethod::ANYDAY} instead. */
    const ANYDAY = PaymentMethod::ANYDAY->value;
    /** @deprecated Use {@see PaymentMethod::APPLE_PAY} instead. */
    const APPLE_PAY = PaymentMethod::APPLE_PAY->value;
    /** @deprecated Use {@see PaymentMethod::CARD} instead. */
    const CARD = PaymentMethod::CARD->value;
    /** @deprecated Use {@see PaymentMethod::GOOGLE_PAY} instead. */
    const GOOGLE_PAY = PaymentMethod::GOOGLE_PAY->value;
    /** @deprecated Use {@see PaymentMethod::KLARNA} instead. */
    const KLARNA = PaymentMethod::KLARNA->value;
    /** @deprecated Use {@see PaymentMethod::MOBILEPAY} instead. */
    const MOBILEPAY = PaymentMethod::MOBILEPAY->value;
    /** @deprecated Use {@see PaymentMethod::MOBILEPAY_CHECKOUT} instead. */
    const MOBILEPAY_CHECKOUT = PaymentMethod::MOBILEPAY_CHECKOUT->value;
    /** @deprecated Use {@see PaymentMethod::PAYPAL} instead. */
    const PAYPAL = PaymentMethod::PAYPAL->value;
    /** @deprecated Use {@see PaymentMethod::SWISH} instead. */
    const SWISH = PaymentMethod::SWISH->value;
    /** @deprecated Use {@see PaymentMethod::VIABILL} instead. */
    const VIABILL = PaymentMethod::VIABILL->value;
    /** @deprecated Use {@see PaymentMethod::VIPPS} instead. */
    const VIPPS = PaymentMethod::VIPPS->value;

    private function __construct() {
        // Static class, should never be instantiated
    }

}
