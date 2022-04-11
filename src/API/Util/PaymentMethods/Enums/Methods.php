<?php

namespace OnPay\API\Util\PaymentMethods\Enums;

final class Methods {
    const CARD = 'card';
    const MOBILEPAY_CHECKOUT = 'mobilepay_checkout';
    const ANYDAY = 'anyday';
    const PAYPAL = 'paypal';
    const SWISH = 'swish';
    const VIABILL = 'viabill';
    const APPLE_PAY = 'applepay';
    const GOOGLE_PAY = 'googlepay';
    const MOBILEPAY = 'mobilepay';
    const VIPPS = 'vipps';

    private function __construct() {
        // Static class, should never be instantiated
    }

}
