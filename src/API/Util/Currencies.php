<?php

namespace OnPay\API\Util;

use OnPay\API\Exception\ApiException;

class Currencies {
    /**
     * Array of accepted currencies as key, exponent as value
     */
    const ACCEPTED_CURRENCIES = [
        36 => 2, // AUD
        124 => 2, // CAD
        156 => 2, // CNY
        203 => 2, // CZK
        208 => 2, // DKK
        352 => 0, // ISK - Historically was 2, but we follow the post-2017 standard
        356 => 2, // INR
        392 => 0, // JPY
        554 => 2, // NZD
        578 => 2, // NOK
        643 => 2, // RUB
        702 => 2, // SGD
        710 => 2, // ZAR
        748 => 2, // SZL
        752 => 2, // SEK
        756 => 2, // CHF
        818 => 2, // EGP
        826 => 2, // GBP
        840 => 2, // USD
        978 => 2, // EUR
        980 => 2, // UAH
        985 => 2, // PLN
        986 => 2, // BRL
    ];

    /**
     * @param int $currencyCode
     * @return int
     * @throws ApiException
     */
    public function getCurrencyExponent($currencyCode) {
        $currencyArray = self::ACCEPTED_CURRENCIES;
        if (!isset($currencyArray[$currencyCode])) {
            throw new ApiException("Invalid Currency Code: $currencyCode - No exponent available.");
        }
        return $currencyArray[$currencyCode];
    }

}
