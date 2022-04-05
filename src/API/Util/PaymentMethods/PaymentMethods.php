<?php

namespace OnPay\API\Util\PaymentMethods;

use OnPay\API\Exception\ApiException;
use OnPay\API\Util\Currency;
use OnPay\API\Util\PaymentMethods\Enums\Methods;
use OnPay\API\Util\PaymentMethods\Methods\PaymentMethodAbstract;
use OnPay\API\Util\PaymentMethods\Methods\PaymentMethodInterface;

class PaymentMethods {

    /**
     * @var PaymentMethodInterface[]
     */
    private $paymentMethods = [];

    /**
     * @throws ApiException
     */
    public function __construct() {
        $this->populatePaymentMethods();
    }

    /**
     * @return void
     * @throws ApiException
     */
    private function populatePaymentMethods() {
        foreach (Methods::ALL_METHODS as $method) {
            $className = 'OnPay\\API\\Util\\PaymentMethods\\Methods\\' . ucfirst($method);
            if (!class_exists($className)) {
                throw new ApiException($method . " is not a configured payment method.");
            }
            /**
             * @var PaymentMethodAbstract $paymentMethod
             */
            $this->paymentMethods[] = new $className();
        }
    }

    /**
     * @param string $method
     * @return Currency[]
     */
    public function getCurrenciesByMethod($method) {
        $currencies = [];

        foreach ($this->paymentMethods as $paymentMethod) {
            if (strtolower($paymentMethod->getName()) === strtolower($method)) {
                $currencies = $paymentMethod->getCurrencies();
                break;
            }
        }
        return $currencies;
    }

    /**
     * @param Currency $currency
     * @return PaymentMethodInterface[]
     */
    public function getPaymentMethodsByCurrency(Currency $currency) {
        $availableMethods = [];

        foreach ($this->paymentMethods as $paymentMethod) {
            if ($paymentMethod->isAvailableForCurrency($currency)) {
                $availableMethods[] = $paymentMethod;
            }
        }
        return $availableMethods;
    }

    /**
     * @return PaymentMethodInterface[]
     */
    public function getAllPaymentMethods() {
        return $this->paymentMethods;
    }
}
