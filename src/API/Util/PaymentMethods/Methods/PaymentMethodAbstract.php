<?php

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Exception\ApiException;
use OnPay\API\Util\Currencies;
use OnPay\API\Util\Currency;
use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;

/**
 * @internal Internal use only
 */
abstract class PaymentMethodAbstract implements PaymentMethodInterface {

    const CURRENCIES = [];
    const METHOD_NAME = '';

    /**
     * @param Currency $currency
     * @return bool
     * @internal Internal use only
     */
    public function isAvailableForCurrency(Currency $currency) {
        if (static::CURRENCIES[0] === CurrencyCodes::ALL_CURRENCY_CODES) {
            return true;
        }
        return in_array($currency->getAlpha3(), static::CURRENCIES, true);
    }

    /**
     * @return array
     * @throws ApiException
     * @internal Internal use only
     */
    public function getCurrencies() {
        $currencies = [];
        if (static::CURRENCIES[0] === CurrencyCodes::ALL_CURRENCY_CODES) {
            foreach (Currencies::CURRENCIES as $currencyCode => $currencyData) {
                $currencies[] = new Currency($currencyCode);
            }
        } else {
            foreach (static::CURRENCIES as $currencyCode) {
                $currencies[] = new Currency($currencyCode);
            }
        }
        return $currencies;
    }

    /**
     * @return string
     * @internal Internal use only
     */
    public function getName() {
        return static::METHOD_NAME;
    }

}
