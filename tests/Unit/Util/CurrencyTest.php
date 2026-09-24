<?php

namespace Tests\Unit\Util;

use OnPay\API\Enum\PaymentMethod;
use OnPay\API\Exception\ApiException;
use OnPay\API\Util\Currency;
use OnPay\API\Util\PaymentMethods\Enums\Methods;
use OnPay\API\Util\PaymentMethods\Methods\PaymentMethodInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for the {@see Currency} value object.
 */
class CurrencyTest extends TestCase
{
    public function testConstructFromAlpha3ResolvesMetadata(): void
    {
        $currency = new Currency('DKK');

        $this->assertSame('DKK', $currency->getAlpha3());
        $this->assertSame(208, $currency->getISO4217());
        $this->assertSame(2, $currency->getExponent());
    }

    public function testConstructFromNumericIso4217ResolvesAlpha3(): void
    {
        // An int can only be a numeric code, so the constructor dispatches to the
        // ISO4217 lookup rather than the alpha-3 one.
        $currency = new Currency(208);

        $this->assertSame('DKK', $currency->getAlpha3());
        $this->assertSame(208, $currency->getISO4217());
        $this->assertSame(2, $currency->getExponent());
    }

    public function testConstructFromZeroExponentCurrency(): void
    {
        $currency = new Currency('JPY');

        $this->assertSame('JPY', $currency->getAlpha3());
        $this->assertSame(392, $currency->getISO4217());
        $this->assertSame(0, $currency->getExponent());
    }

    public function testConstructThrowsForUnsupportedCurrency(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Unsupported currency provided: XXX');

        new Currency('XXX');
    }

    public function testConstructIsCaseInsensitiveForAlpha3(): void
    {
        // The alpha-3 lookup folds case and answers with the canonical spelling, so
        // getAlpha3() is uppercase whatever the caller passed.
        foreach (['dkk', 'Dkk', 'dKK'] as $spelling) {
            $currency = new Currency($spelling);

            $this->assertSame('DKK', $currency->getAlpha3());
            $this->assertSame(208, $currency->getISO4217());
        }
    }

    public function testConstructThrowsForNumericStringIso4217(): void
    {
        // A numeric code must be passed as an int: a string is only ever read as an
        // alpha-3 code, and '208' is not one.
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Unsupported currency provided: 208');

        new Currency('208');
    }

    public function testGetPaymentMethodsReturnsAvailableMethods(): void
    {
        $methods = (new Currency('DKK'))->getPaymentMethods();

        $this->assertNotEmpty($methods);
        $this->assertContainsOnlyInstancesOf(PaymentMethodInterface::class, $methods);

        $names = array_map(static fn (PaymentMethodInterface $m) => $m->getName(), $methods);
        // DKK is supported by Anyday and the all-currency methods, but not Swish (SEK only).
        $this->assertContains(Methods::ANYDAY, $names);
        $this->assertContains(Methods::CARD, $names);
        $this->assertNotContains(Methods::SWISH, $names);
    }

    public function testIsPaymentMethodAvailableReturnsTrueWhenPresent(): void
    {
        $this->assertTrue((new Currency('DKK'))->isPaymentMethodAvailable(Methods::CARD));
    }

    public function testIsPaymentMethodAvailableReturnsFalseWhenAbsent(): void
    {
        // Swish is SEK-only, so it is not available for DKK.
        $this->assertFalse((new Currency('DKK'))->isPaymentMethodAvailable(Methods::SWISH));
    }

    public function testIsPaymentMethodAvailableAcceptsAPaymentMethodEnum(): void
    {
        $currency = new Currency('DKK');

        $this->assertTrue($currency->isPaymentMethodAvailable(PaymentMethod::CARD));
        $this->assertFalse($currency->isPaymentMethodAvailable(PaymentMethod::SWISH));
    }

    public function testIsPaymentMethodAvailableReturnsFalseForAnUnknownMethodString(): void
    {
        $this->assertFalse((new Currency('DKK'))->isPaymentMethodAvailable('some-future-method'));
    }
}
