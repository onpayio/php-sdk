<?php

namespace OnPay\API\Util;

class Currencies {

    const ALPHA3_CURRENCIES = array(
        'AUD' => array('exponent'=>2, 'iso4217' => 36),
        'CAD' => array('exponent'=>2, 'iso4217' => 124),
        'CNY' => array('exponent'=>2, 'iso4217' => 156),
        'CZK' => array('exponent'=>2, 'iso4217' => 203),
        'DKK' => array('exponent'=>2, 'iso4217' => 208),
        'ISK' => array('exponent'=>0, 'iso4217' => 352),
        'INR' => array('exponent'=>2, 'iso4217' => 356),
        'JPY' => array('exponent'=>0, 'iso4217' => 392),
        'NZD' => array('exponent'=>2, 'iso4217' => 554),
        'NOK' => array('exponent'=>2, 'iso4217' => 578),
        'RUB' => array('exponent'=>2, 'iso4217' => 643),
        'SGD' => array('exponent'=>2, 'iso4217' => 702),
        'ZAR' => array('exponent'=>2, 'iso4217' => 710),
        'SZL' => array('exponent'=>2, 'iso4217' => 748),
        'SEK' => array('exponent'=>2, 'iso4217' => 752),
        'CHF' => array('exponent'=>2, 'iso4217' => 756),
        'ECP' => array('exponent'=>2, 'iso4217' => 818),
        'GBP' => array('exponent'=>2, 'iso4217' => 826),
        'USD' => array('exponent'=>2, 'iso4217' => 840),
        'EUR' => array('exponent'=>2, 'iso4217' => 978),
        'UAH' => array('exponent'=>2, 'iso4217' => 980),
        'PLN' => array('exponent'=>2, 'iso4217' => 985),
        'BRL' => array('exponent'=>2, 'iso4217' => 986),
    );

    const ISO4217_CURRENCIES = array(
        36 =>  array('exponent'=>2, 'alpha3' => 'AUD'),
        124 => array('exponent'=>2, 'alpha3' => 'CAD'),
        156 => array('exponent'=>2, 'alpha3' => 'CNY'),
        203 => array('exponent'=>2, 'alpha3' => 'CZK'),
        208 => array('exponent'=>2, 'alpha3' => 'DKK'),
        352 => array('exponent'=>0, 'alpha3' => 'ISK'),
        356 => array('exponent'=>2, 'alpha3' => 'INR'),
        392 => array('exponent'=>0, 'alpha3' => 'JPY'),
        554 => array('exponent'=>2, 'alpha3' => 'NZD'),
        578 => array('exponent'=>2, 'alpha3' => 'NOK'),
        643 => array('exponent'=>2, 'alpha3' => 'RUB'),
        702 => array('exponent'=>2, 'alpha3' => 'SGD'),
        710 => array('exponent'=>2, 'alpha3' => 'ZAR'),
        748 => array('exponent'=>2, 'alpha3' => 'SZL'),
        752 => array('exponent'=>2, 'alpha3' => 'SEK'),
        756 => array('exponent'=>2, 'alpha3' => 'CHF'),
        818 => array('exponent'=>2, 'alpha3' => 'ECP'),
        826 => array('exponent'=>2, 'alpha3' => 'GBP'),
        840 => array('exponent'=>2, 'alpha3' => 'USD'),
        978 => array('exponent'=>2, 'alpha3' => 'EUR'),
        980 => array('exponent'=>2, 'alpha3' => 'UAH'),
        985 => array('exponent'=>2, 'alpha3' => 'PLN'),
        986 => array('exponent'=>2, 'alpha3' => 'BRL'),
    );

    /**
     * @param int $alpha3
     * @return bool
     */
    public function isValidAlpha3($alpha3) {
        $currencyArray = self::ALPHA3_CURRENCIES;
        if (!isset($currencyArray[$alpha3])) {
            return false;
        }
        return true;
    }

    /**
     * @param int $iso4217
     * @return bool
     */
    public function isValidISO4217($iso4217) {
        $currencyArray = self::ISO4217_CURRENCIES;
        if (!isset($currencyArray[$iso4217])) {
            return false;
        }
        return true;
    }

}
