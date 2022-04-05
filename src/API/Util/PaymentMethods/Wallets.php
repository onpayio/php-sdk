<?php

namespace OnPay\API\Util\PaymentMethods;

final class Wallets {
    const APPLE_PAY = 'applepay';
    const GOOGLE_PAY = 'googlepay';
    const MOBILEPAY = 'mobilepay';
    const VIPPS = 'vipps';

    private function __construct() {
        // Static class, should never be instantiated
    }

}
