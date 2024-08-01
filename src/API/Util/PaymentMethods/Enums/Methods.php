<?php

namespace OnPay\API\Util\PaymentMethods\Enums;

final class Methods {
    const ANYDAY = 'anyday';
    const APPLE_PAY = 'applepay';
    const CARD = 'card';
    const GOOGLE_PAY = 'googlepay';
    const KLARNA = 'klarna';
    const MOBILEPAY = 'mobilepay';
    const MOBILEPAY_CHECKOUT = 'mobilepay_checkout';
    const PAYPAL = 'paypal';
    const SWISH = 'swish';
    const VIABILL = 'viabill';
    const VIPPS = 'vipps';

    private function __construct() {
        // Static class, should never be instantiated
    }

}
