<?php

namespace OnPay\API\Util;

use OnPay\API\Exception\ApiException;
use OnPay\API\Util\PaymentMethods\Methods\PaymentMethodInterface;
use OnPay\API\Util\PaymentMethods\PaymentMethods;

/**
 * This currency helper class will assist with ensuring currencies used are supported and in the correct format
 */
class Currency {

    /**
     * @var string
     */
    private $alpha3;
    /**
     * @var int
     */
    private $ISO4217;
    /**
     * @var int
     */
    private $exponent;

    /**
     * @param string|int $currencyCode This can be either a valid ISO4217 value or a valid Alpha3 value.
     * @throws ApiException
     */
    public function __construct($currencyCode) {
        $alpha3 = Currencies::isValidAlpha3($currencyCode);
        if ($alpha3 === false) {
            $alpha3 = Currencies::isValidISO4217($currencyCode);
        }
        if ($alpha3 === false) {
            throw new ApiException("Unsupported currency provided: " . $currencyCode);
        }
        $this->alpha3 = $alpha3;
        $this->ISO4217 = Currencies::CURRENCIES[$alpha3]['ISO4217'];
        $this->exponent = Currencies::CURRENCIES[$alpha3]['exponent'];
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
        return $this->ISO4217;
    }

    /**
     * @return PaymentMethodInterface[]
     */
    public function getPaymentMethods() {
        return (new PaymentMethods())->getPaymentMethodsByCurrency($this);
    }

    /**
     * @param string $paymentMethodName
     * @return bool
     */
    public function isPaymentMethodAvailable($paymentMethodName) {
        $availablePaymentMethods = $this->getPaymentMethods();
        foreach ($availablePaymentMethods as $availablePaymentMethod) {
            if ($availablePaymentMethod->getName() === $paymentMethodName) {
                return true;
            }
        }
        return false;
    }

}

