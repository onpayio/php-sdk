<?php

declare(strict_types=1);

namespace OnPay\API\Util;

use OnPay\API\Enum\PaymentMethod;
use OnPay\API\Exception\ApiException;
use OnPay\API\Util\PaymentMethods\Methods\PaymentMethodInterface;
use OnPay\API\Util\PaymentMethods\PaymentMethods;

/**
 * This currency helper class will assist with ensuring currencies used are supported and in the correct format
 */
final class Currency {

    /**
     * @var string
     */
    private string $alpha3;
    /**
     * @var int
     */
    private int $ISO4217;
    /**
     * @var int
     */
    private int $exponent;

    /**
     * @param string|int $currencyCode An alpha-3 code as a string ('DKK'), or an ISO 4217
     *                                 numeric code as an int (208).
     * @throws ApiException
     */
    public function __construct(int|string $currencyCode) {
        if (is_int($currencyCode)) {
            $alpha3 = Currencies::isValidISO4217($currencyCode);
        } else {
            $alpha3 = Currencies::isValidAlpha3($currencyCode);
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
    public function getExponent(): int {
        return $this->exponent;
    }

    /**
     * @return string
     */
    public function getAlpha3(): string {
        return $this->alpha3;
    }

    /**
     * @return int
     */
    public function getISO4217(): int {
        return $this->ISO4217;
    }

    /**
     * @return PaymentMethodInterface[]
     */
    public function getPaymentMethods(): array {
        return (new PaymentMethods())->getPaymentMethodsByCurrency($this);
    }

    /**
     * @param string|PaymentMethod $paymentMethodName A {@see PaymentMethod} case, or a raw
     *                                                method identifier. An unknown
     *                                                identifier returns false.
     * @return bool
     */
    public function isPaymentMethodAvailable(string|PaymentMethod $paymentMethodName): bool {
        $name = $paymentMethodName instanceof PaymentMethod ? $paymentMethodName->value : $paymentMethodName;
        $availablePaymentMethods = $this->getPaymentMethods();
        foreach ($availablePaymentMethods as $availablePaymentMethod) {
            if ($availablePaymentMethod->getName() === $name) {
                return true;
            }
        }
        return false;
    }

}

