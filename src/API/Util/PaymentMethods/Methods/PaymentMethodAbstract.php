<?php

declare(strict_types=1);

namespace OnPay\API\Util\PaymentMethods\Methods;

use OnPay\API\Enum\PaymentMethod;
use OnPay\API\Exception\ApiException;
use OnPay\API\Util\Currencies;
use OnPay\API\Util\Currency;
use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;

/**
 * @internal Internal use only
 */
abstract class PaymentMethodAbstract implements PaymentMethodInterface {

    /**
     * @var list<string>
     */
    const CURRENCIES = [];
    /**
     * @deprecated Use {@see PaymentMethodAbstract::getMethod()} or the {@see PaymentMethod}
     *             enum instead. Every concrete method class still declares this constant
     *             with its current value.
     * @var string
     */
    const METHOD_NAME = '';

    /**
     * @param Currency $currency
     * @return bool
     * @internal Internal use only
     */
    public function isAvailableForCurrency(Currency $currency): bool {
        if (in_array(CurrencyCodes::ALL_CURRENCY_CODES, static::CURRENCIES, true)) {
            return true;
        }
        return in_array($currency->getAlpha3(), static::CURRENCIES, true);
    }

    /**
     * @return Currency[]
     * @throws ApiException
     * @internal Internal use only
     */
    public function getCurrencies(): array {
        $currencies = [];
        if (in_array(CurrencyCodes::ALL_CURRENCY_CODES, static::CURRENCIES, true)) {
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
    public function getName(): string {
        return $this->getMethod()->value;
    }

}
