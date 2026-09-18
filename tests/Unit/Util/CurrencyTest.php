<?php

namespace Tests\Unit\Util;

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
        // alpha3 lookup fails for an int, so the ISO4217 branch resolves it instead.
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

    public function testConstructThrowsForNumericStringIso4217(): void
    {
        // Pinned behaviour: ISO4217 matching is strict (=== against int), so passing
        // the numeric code as a string is rejected even though the docblock advertises
        // "a valid ISO4217 value". '208' as a string therefore throws.
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
}
