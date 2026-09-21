<?php

declare(strict_types=1);

namespace OnPay\API\Util\PaymentMethods\Enums;

/**
 * Alpha-3 names for the currencies the SDK knows, plus the {@see CurrencyCodes::ALL_CURRENCY_CODES}
 * sentinel used by a payment method that accepts every currency.
 *
 * Deliberately not a PHP enum: the sentinel is not a currency, and the codes are used as keys
 * into {@see \OnPay\API\Util\Currencies::CURRENCIES}, which is the single source of truth for
 * the supported currencies — this class only names them. There is nothing to consolidate.
 */
final class CurrencyCodes {
    const AUD = 'AUD';
    const CAD = 'CAD';
    const CNY = 'CNY';
    const CZK = 'CZK';
    const DKK = 'DKK';
    const ISK = 'ISK';
    const INR = 'INR';
    const JPY = 'JPY';
    const NZD = 'NZD';
    const NOK = 'NOK';
    const RUB = 'RUB';
    const SGD = 'SGD';
    const ZAR = 'ZAR';
    const SZL = 'SZL';
    const SEK = 'SEK';
    const CHF = 'CHF';
    const EGP = 'EGP';
    const GBP = 'GBP';
    const USD = 'USD';
    const EUR = 'EUR';
    const UAH = 'UAH';
    const PLN = 'PLN';
    const BRL = 'BRL';
    const ALL_CURRENCY_CODES = 'ALL_CURRENCY_CODES';
}
