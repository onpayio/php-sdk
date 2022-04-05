<?php

namespace OnPay\API\Util\PaymentMethods\Enums;

final class Methods {
    const CARD = 'card';
    const MOBILEPAY_CHECKOUT = 'mobilepay_checkout';

    const ALL_METHODS = [
        Acquirers::ANYDAY,
        Wallets::APPLE_PAY,
        self::CARD,
        Wallets::GOOGLE_PAY,
        Wallets::MOBILEPAY,
        self::MOBILEPAY_CHECKOUT,
        Acquirers::PAYPAL,
        Acquirers::VIABILL,
        Wallets::VIPPS,
        Acquirers::SWISH,
    ];

    private function __construct() {
        // Static class, should never be instantiated
    }

}
