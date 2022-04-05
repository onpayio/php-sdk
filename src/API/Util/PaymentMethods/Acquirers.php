<?php

namespace OnPay\API\Util\PaymentMethods;

final class Acquirers {

    const ANYDAY = 'anyday';
    const BAMBORA = 'bambora';
    const CLEARHAUS = 'clearhaus';
    const NETS = 'nets';
    const PAYPAL = 'paypal';
    const SWEDBANK = 'swedbank';
    const SWISH = 'swish';
    const VIABILL = 'viabill';

    private function __construct() {
        // Static class, should never be instantiated
    }

}
