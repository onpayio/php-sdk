<?php

namespace OnPay\API\Util;

class Currencies {

    /**
     * This array contains all the supported currencies and is based on the following documentation:
     * https://onpay.io/docs/technical/index.html#accepted-currencies
     */
    const CURRENCIES = [
        'AUD' => ['exponent' => 2, 'ISO4217' => 36],
        'CAD' => ['exponent' => 2, 'ISO4217' => 124],
        'CNY' => ['exponent' => 2, 'ISO4217' => 156],
        'CZK' => ['exponent' => 2, 'ISO4217' => 203],
        'DKK' => ['exponent' => 2, 'ISO4217' => 208],
        'ISK' => ['exponent' => 0, 'ISO4217' => 352],
        'INR' => ['exponent' => 2, 'ISO4217' => 356],
        'JPY' => ['exponent' => 0, 'ISO4217' => 392],
        'NZD' => ['exponent' => 2, 'ISO4217' => 554],
        'NOK' => ['exponent' => 2, 'ISO4217' => 578],
        'RUB' => ['exponent' => 2, 'ISO4217' => 643],
        'SGD' => ['exponent' => 2, 'ISO4217' => 702],
        'ZAR' => ['exponent' => 2, 'ISO4217' => 710],
        'SZL' => ['exponent' => 2, 'ISO4217' => 748],
        'SEK' => ['exponent' => 2, 'ISO4217' => 752],
        'CHF' => ['exponent' => 2, 'ISO4217' => 756],
        'EGP' => ['exponent' => 2, 'ISO4217' => 818],
        'GBP' => ['exponent' => 2, 'ISO4217' => 826],
        'USD' => ['exponent' => 2, 'ISO4217' => 840],
        'EUR' => ['exponent' => 2, 'ISO4217' => 978],
        'UAH' => ['exponent' => 2, 'ISO4217' => 980],
        'PLN' => ['exponent' => 2, 'ISO4217' => 985],
        'BRL' => ['exponent' => 2, 'ISO4217' => 986],
    ];

    /**
     * @param string $alpha3
     * @return bool|string
     */
    public function isValidAlpha3($alpha3) {
        $currencyArray = self::CURRENCIES;
        if (!isset($currencyArray[$alpha3])) {
            return false;
        }
        return $alpha3;
    }

    /**
     * @param int $ISO4217
     * @return bool|string
     */
    public function isValidISO4217($ISO4217) {
        $currencyArray = self::CURRENCIES;
        foreach ($currencyArray as $alpha3 => $currencyData) {
            if ($currencyData['ISO4217'] === $ISO4217) {
                return $alpha3;
            }
        }
        return false;
    }

}
