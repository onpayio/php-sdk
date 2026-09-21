<?php

declare(strict_types=1);

namespace OnPay\API\Util\PaymentMethods;

use OnPay\API\Enum\PaymentMethod;
use OnPay\API\Exception\ApiException;
use OnPay\API\Util\Currency;
use OnPay\API\Util\PaymentMethods\Methods\Anyday;
use OnPay\API\Util\PaymentMethods\Methods\ApplePay;
use OnPay\API\Util\PaymentMethods\Methods\Card;
use OnPay\API\Util\PaymentMethods\Methods\GooglePay;
use OnPay\API\Util\PaymentMethods\Methods\MobilePay;
use OnPay\API\Util\PaymentMethods\Methods\MobilePayCheckout;
use OnPay\API\Util\PaymentMethods\Methods\PaymentMethodInterface;
use OnPay\API\Util\PaymentMethods\Methods\PayPal;
use OnPay\API\Util\PaymentMethods\Methods\Klarna;
use OnPay\API\Util\PaymentMethods\Methods\Swish;
use OnPay\API\Util\PaymentMethods\Methods\ViaBill;
use OnPay\API\Util\PaymentMethods\Methods\Vipps;

class PaymentMethods {

    /**
     * @var PaymentMethodInterface[]
     */
    private array $paymentMethods = [];

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
    private function populatePaymentMethods(): void {
        $this->paymentMethods[] = new Anyday();
        $this->paymentMethods[] = new ApplePay();
        $this->paymentMethods[] = new Card();
        $this->paymentMethods[] = new GooglePay();
        $this->paymentMethods[] = new Klarna();
        $this->paymentMethods[] = new MobilePay();
        $this->paymentMethods[] = new MobilePayCheckout();
        $this->paymentMethods[] = new PayPal();
        $this->paymentMethods[] = new Swish();
        $this->paymentMethods[] = new ViaBill();
        $this->paymentMethods[] = new Vipps();
    }

    /**
     * @param string|PaymentMethod $method A {@see PaymentMethod} case, or a raw method
     *                                     identifier. An unknown identifier yields an
     *                                     empty array rather than an error.
     * @return Currency[]
     */
    public function getCurrenciesByMethod(string|PaymentMethod $method): array {
        $currencies = [];
        $methodName = $method instanceof PaymentMethod ? $method->value : $method;

        foreach ($this->paymentMethods as $paymentMethod) {
            if (strtolower($paymentMethod->getName()) === strtolower($methodName)) {
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
    public function getPaymentMethodsByCurrency(Currency $currency): array {
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
    public function getAllPaymentMethods(): array {
        return $this->paymentMethods;
    }
}
