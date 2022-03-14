<?php

namespace OnPay\API\Util;

use OnPay\API\Exception\ApiException;

/**
 * This currency helper class will assist with ensuring currencies used are supported and in the correct format
 */
class Currency extends Currencies {

    /**
     * @var string
     */
    private $alpha3;
    /**
     * @var int
     */
    private $iso4217;
    /**
     * @var int
     */
    private $exponent;

    /**
     * @param string|int $currencyCode This can be either a valid ISO4217 value or a valid Alpha3 value.
     * @throws ApiException
     */
    public function __construct($currencyCode) {
        if ($this->isValidAlpha3($currencyCode)) {
            $this->alpha3 = $currencyCode;
            $this->assignValuesFromAlpha3();
        } elseif ($this->isValidISO4217($currencyCode)) {
            $this->iso4217 = $currencyCode;
            $this->assignValuesFromIso4217();
        } else {
            throw new ApiException("Unsupported currency provided: " . $currencyCode);
        }
    }

    /**
     * @return int
     */
    public function getExponent() {
        return $this->exponent;
    }

    /**
     * @return string
     */
    public function getAlpha3() {
        return $this->alpha3;
    }

    /**
     * @return int
     */
    public function getISO4217() {
        return $this->iso4217;
    }

    private function assignValuesFromAlpha3() {
        $this->iso4217 = self::ALPHA3_CURRENCIES[$this->alpha3]['iso4217'];
        $this->exponent = self::ALPHA3_CURRENCIES[$this->alpha3]['exponent'];
    }

    private function assignValuesFromIso4217() {
        $this->alpha3 = self::ISO4217_CURRENCIES[$this->iso4217]['alpha3'];
        $this->exponent = self::ISO4217_CURRENCIES[$this->iso4217]['exponent'];
    }
}