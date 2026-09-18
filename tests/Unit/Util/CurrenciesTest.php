<?php

namespace Tests\Unit\Util;

use OnPay\API\Util\Currencies;
use OnPay\API\Util\PaymentMethods\Enums\CurrencyCodes;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for the {@see Currencies} lookup table.
 */
class CurrenciesTest extends TestCase
{
    public function testCurrenciesTableHoldsKnownValues(): void
    {
        // Spot-check real, known entries from the supported-currencies table.
        $this->assertSame(208, Currencies::CURRENCIES[CurrencyCodes::DKK]['ISO4217']);
        $this->assertSame(2, Currencies::CURRENCIES[CurrencyCodes::DKK]['exponent']);

        // JPY and ISK are the zero-exponent (no minor unit) currencies.
        $this->assertSame(392, Currencies::CURRENCIES[CurrencyCodes::JPY]['ISO4217']);
        $this->assertSame(0, Currencies::CURRENCIES[CurrencyCodes::JPY]['exponent']);
        $this->assertSame(352, Currencies::CURRENCIES[CurrencyCodes::ISK]['ISO4217']);
        $this->assertSame(0, Currencies::CURRENCIES[CurrencyCodes::ISK]['exponent']);

        $this->assertSame(978, Currencies::CURRENCIES[CurrencyCodes::EUR]['ISO4217']);
        $this->assertCount(23, Currencies::CURRENCIES);
    }

    public function testIsValidAlpha3ReturnsCodeForKnownCurrency(): void
    {
        $this->assertSame('DKK', Currencies::isValidAlpha3('DKK'));
        $this->assertSame('EUR', Currencies::isValidAlpha3('EUR'));
    }

    public function testIsValidAlpha3ReturnsFalseForUnknownCurrency(): void
    {
        $this->assertFalse(Currencies::isValidAlpha3('XXX'));
    }

    public function testIsValidAlpha3IsCaseSensitive(): void
    {
        // Pinned behaviour: alpha3 lookup is case-sensitive (array key match), so a
        // lowercase code is not accepted, unlike the case-insensitive method lookup
        // in PaymentMethods::getCurrenciesByMethod.
        $this->assertFalse(Currencies::isValidAlpha3('dkk'));
    }

    public function testIsValidISO4217ReturnsCodeForKnownNumericCode(): void
    {
        $this->assertSame('DKK', Currencies::isValidISO4217(208));
        $this->assertSame('EUR', Currencies::isValidISO4217(978));
    }

    public function testIsValidISO4217ReturnsFalseForUnknownNumericCode(): void
    {
        $this->assertFalse(Currencies::isValidISO4217(999));
    }

    public function testIsValidISO4217IsStrictlyTyped(): void
    {
        // Pinned behaviour: the comparison uses === against an int, so a numeric
        // string does not match even though the docblock accepts "a valid ISO4217
        // value". Passing '208' as a string returns false.
        $this->assertFalse(Currencies::isValidISO4217('208'));
    }

    /**
     * Currencies is a static class whose constructor is private and empty ("should
     * never be instantiated"). Invoked via reflection purely for coverage of the
     * otherwise-unreachable private constructor.
     */
    public function testConstructorIsPrivate(): void
    {
        $reflection = new \ReflectionClass(Currencies::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPrivate());

        $instance = $reflection->newInstanceWithoutConstructor();
        $constructor->invoke($instance);
        $this->assertInstanceOf(Currencies::class, $instance);
    }
}
