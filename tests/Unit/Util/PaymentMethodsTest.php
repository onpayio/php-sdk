<?php

namespace Tests\Unit\Util;

use OnPay\API\Util\Currency;
use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;
use OnPay\API\Util\PaymentMethods\Enums\Methods;
use OnPay\API\Util\PaymentMethods\Methods\Anyday;
use OnPay\API\Util\PaymentMethods\Methods\ApplePay;
use OnPay\API\Util\PaymentMethods\Methods\Card;
use OnPay\API\Util\PaymentMethods\Methods\GooglePay;
use OnPay\API\Util\PaymentMethods\Methods\Klarna;
use OnPay\API\Util\PaymentMethods\Methods\MobilePay;
use OnPay\API\Util\PaymentMethods\Methods\MobilePayCheckout;
use OnPay\API\Util\PaymentMethods\Methods\PaymentMethodInterface;
use OnPay\API\Util\PaymentMethods\Methods\PayPal;
use OnPay\API\Util\PaymentMethods\Methods\Swish;
use OnPay\API\Util\PaymentMethods\Methods\ViaBill;
use OnPay\API\Util\PaymentMethods\Methods\Vipps;
use OnPay\API\Util\PaymentMethods\PaymentMethods;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for {@see PaymentMethods}, the shared {@see \OnPay\API\Util\PaymentMethods\Methods\PaymentMethodAbstract}
 * behaviour (exercised through the concrete method classes) and the supporting enums.
 */
class PaymentMethodsTest extends TestCase
{
    public function testGetAllPaymentMethodsReturnsEveryRegisteredMethod(): void
    {
        $methods = (new PaymentMethods())->getAllPaymentMethods();

        $this->assertCount(11, $methods);
        $this->assertContainsOnlyInstancesOf(PaymentMethodInterface::class, $methods);

        $classes = array_map('get_class', $methods);
        $this->assertSame([
            Anyday::class,
            ApplePay::class,
            Card::class,
            GooglePay::class,
            Klarna::class,
            MobilePay::class,
            MobilePayCheckout::class,
            PayPal::class,
            Swish::class,
            ViaBill::class,
            Vipps::class,
        ], $classes);
    }

    public function testGetCurrenciesByMethodReturnsCurrenciesForMatch(): void
    {
        // Mixed case exercises the case-insensitive strtolower comparison on both sides.
        $currencies = (new PaymentMethods())->getCurrenciesByMethod('MobilePay');

        $this->assertContainsOnlyInstancesOf(Currency::class, $currencies);
        $alpha3 = array_map(static fn (Currency $c) => $c->getAlpha3(), $currencies);
        $this->assertSame(['DKK', 'NOK', 'SEK', 'GBP', 'USD', 'EUR'], $alpha3);
    }

    public function testGetCurrenciesByMethodReturnsEmptyForUnknownMethod(): void
    {
        $this->assertSame([], (new PaymentMethods())->getCurrenciesByMethod('does-not-exist'));
    }

    public function testGetPaymentMethodsByCurrencyFiltersByAvailability(): void
    {
        $methods = (new PaymentMethods())->getPaymentMethodsByCurrency(new Currency('SEK'));

        $names = array_map(static fn (PaymentMethodInterface $m) => $m->getName(), $methods);
        // Swish is SEK-only and must appear; Anyday is DKK-only and must not.
        $this->assertContains(Methods::SWISH, $names);
        $this->assertContains(Methods::CARD, $names);
        $this->assertNotContains(Methods::ANYDAY, $names);
    }

    // --- PaymentMethodAbstract behaviour, exercised through concrete method classes ---

    public function testGetNameReturnsMethodNameConstant(): void
    {
        $this->assertSame(Methods::ANYDAY, (new Anyday())->getName());
        $this->assertSame(Methods::APPLE_PAY, (new ApplePay())->getName());
        $this->assertSame(Methods::CARD, (new Card())->getName());
        $this->assertSame(Methods::GOOGLE_PAY, (new GooglePay())->getName());
        $this->assertSame(Methods::KLARNA, (new Klarna())->getName());
        $this->assertSame(Methods::MOBILEPAY, (new MobilePay())->getName());
        $this->assertSame(Methods::MOBILEPAY_CHECKOUT, (new MobilePayCheckout())->getName());
        $this->assertSame(Methods::PAYPAL, (new PayPal())->getName());
        $this->assertSame(Methods::SWISH, (new Swish())->getName());
        $this->assertSame(Methods::VIABILL, (new ViaBill())->getName());
        $this->assertSame(Methods::VIPPS, (new Vipps())->getName());
    }

    public function testIsAvailableForCurrencyAllCurrenciesBranch(): void
    {
        // Card declares ALL_CURRENCY_CODES, so it short-circuits to true for anything.
        $card = new Card();
        $this->assertTrue($card->isAvailableForCurrency(new Currency('DKK')));
        $this->assertTrue($card->isAvailableForCurrency(new Currency('JPY')));
    }

    public function testIsAvailableForCurrencyExplicitListBranch(): void
    {
        $anyday = new Anyday();
        // in_array branch: DKK is listed, SEK is not.
        $this->assertTrue($anyday->isAvailableForCurrency(new Currency('DKK')));
        $this->assertFalse($anyday->isAvailableForCurrency(new Currency('SEK')));
    }

    public function testGetCurrenciesAllCurrenciesBranch(): void
    {
        // Card uses ALL_CURRENCY_CODES, expanding to the full Currencies table (23).
        $currencies = (new Card())->getCurrencies();

        $this->assertCount(23, $currencies);
        $this->assertContainsOnlyInstancesOf(Currency::class, $currencies);
    }

    public function testGetCurrenciesExplicitListBranch(): void
    {
        $klarna = (new Klarna())->getCurrencies();
        $this->assertCount(11, $klarna);
        $this->assertContainsOnlyInstancesOf(Currency::class, $klarna);

        $anyday = (new Anyday())->getCurrencies();
        $this->assertCount(1, $anyday);
        $this->assertSame('DKK', $anyday[0]->getAlpha3());
    }

    // --- Enums ---

    public function testMethodsEnumExposesExpectedValues(): void
    {
        $this->assertSame('anyday', Methods::ANYDAY);
        $this->assertSame('applepay', Methods::APPLE_PAY);
        $this->assertSame('card', Methods::CARD);
        $this->assertSame('mobilepay_checkout', Methods::MOBILEPAY_CHECKOUT);
    }

    /**
     * Methods is a static enum-like class with a private, empty constructor. Invoked via
     * reflection purely for coverage of the otherwise-unreachable private constructor.
     */
    public function testMethodsConstructorIsPrivate(): void
    {
        $reflection = new \ReflectionClass(Methods::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPrivate());

        $instance = $reflection->newInstanceWithoutConstructor();
        $constructor->invoke($instance);
        $this->assertInstanceOf(Methods::class, $instance);
    }

    public function testCurrencyCodesEnumExposesExpectedValues(): void
    {
        // CurrencyCodes carries only constants (no executable code); pin a few values.
        $this->assertSame('DKK', CurrencyCodes::DKK);
        $this->assertSame('EUR', CurrencyCodes::EUR);
        $this->assertSame('ALL_CURRENCY_CODES', CurrencyCodes::ALL_CURRENCY_CODES);
    }
}
