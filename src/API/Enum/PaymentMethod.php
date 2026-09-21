<?php

declare(strict_types=1);

namespace OnPay\API\Enum;

/**
 * The payment methods the OnPay payment window accepts.
 *
 * Each case's value is the identifier sent to the gateway as the payment window's
 * `method` field. This enum is the single source of truth for those identifiers:
 * {@see \OnPay\API\PaymentWindow}'s `METHOD_*` constants and the deprecated
 * {@see \OnPay\API\Util\PaymentMethods\Enums\Methods} class are both defined in terms
 * of it.
 *
 * The gateway may offer a method this enum does not list yet, so the SDK never
 * validates an incoming method string against these cases — every API taking a method
 * accepts `string|PaymentMethod`.
 */
enum PaymentMethod: string
{
    case ANYDAY = 'anyday';
    case APPLE_PAY = 'applepay';
    case CARD = 'card';
    case GOOGLE_PAY = 'googlepay';
    case KLARNA = 'klarna';
    case MOBILEPAY = 'mobilepay';
    case MOBILEPAY_CHECKOUT = 'mobilepay_checkout';
    case PAYPAL = 'paypal';
    case SWISH = 'swish';
    case VIABILL = 'viabill';
    case VIPPS = 'vipps';
}
