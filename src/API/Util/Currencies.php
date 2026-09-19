<?php

namespace OnPay\API\Util;

use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;

final class Currencies {

    /**
     * This array contains all the supported currencies and is based on the following documentation:
     * https://onpay.io/docs/technical/index.html#accepted-currencies
     */
    const CURRENCIES = [
        CurrencyCodes::AUD => ['exponent' => 2, 'ISO4217' => 36],
        CurrencyCodes::CAD => ['exponent' => 2, 'ISO4217' => 124],
        CurrencyCodes::CNY => ['exponent' => 2, 'ISO4217' => 156],
        CurrencyCodes::CZK => ['exponent' => 2, 'ISO4217' => 203],
        CurrencyCodes::DKK => ['exponent' => 2, 'ISO4217' => 208],
        CurrencyCodes::ISK => ['exponent' => 0, 'ISO4217' => 352],
        CurrencyCodes::INR => ['exponent' => 2, 'ISO4217' => 356],
        CurrencyCodes::JPY => ['exponent' => 0, 'ISO4217' => 392],
        CurrencyCodes::NZD => ['exponent' => 2, 'ISO4217' => 554],
        CurrencyCodes::NOK => ['exponent' => 2, 'ISO4217' => 578],
        CurrencyCodes::RUB => ['exponent' => 2, 'ISO4217' => 643],
        CurrencyCodes::SGD => ['exponent' => 2, 'ISO4217' => 702],
        CurrencyCodes::ZAR => ['exponent' => 2, 'ISO4217' => 710],
        CurrencyCodes::SZL => ['exponent' => 2, 'ISO4217' => 748],
        CurrencyCodes::SEK => ['exponent' => 2, 'ISO4217' => 752],
        CurrencyCodes::CHF => ['exponent' => 2, 'ISO4217' => 756],
        CurrencyCodes::EGP => ['exponent' => 2, 'ISO4217' => 818],
        CurrencyCodes::GBP => ['exponent' => 2, 'ISO4217' => 826],
        CurrencyCodes::USD => ['exponent' => 2, 'ISO4217' => 840],
        CurrencyCodes::EUR => ['exponent' => 2, 'ISO4217' => 978],
        CurrencyCodes::UAH => ['exponent' => 2, 'ISO4217' => 980],
        CurrencyCodes::PLN => ['exponent' => 2, 'ISO4217' => 985],
        CurrencyCodes::BRL => ['exponent' => 2, 'ISO4217' => 986],
    ];

    private function __construct() {
        // Static class, should never be instantiated
    }

    /**
     * @param int|string $alpha3
     * @return string|false
     */
    public static function isValidAlpha3($alpha3) {
        if (is_string($alpha3) && isset(self::CURRENCIES[$alpha3])) {
            return $alpha3;
        }
        return false;
    }

    /**
     * @param int|string $ISO4217
     * @return string|false
     */
    public static function isValidISO4217($ISO4217) {
        foreach (self::CURRENCIES as $alpha3 => $currencyData) {
            if ($currencyData['ISO4217'] === $ISO4217) {
                return $alpha3;
            }
        }
        return false;
    }

}
