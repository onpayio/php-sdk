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

    /**
     * The parameter is `int`, so what a numeric string does depends on the *caller's*
     * mode: this file is weak-mode, so PHP coerces '208' to 208 before the call and the
     * lookup succeeds. A strict-mode caller gets a TypeError instead — see
     * {@see CurrenciesStrictTypesTest}. Either way the value is never silently mismatched
     * the way the old `int|string` signature allowed.
     */
    public function testIsValidISO4217CoercesANumericStringForAWeakModeCaller(): void
    {
        $this->assertSame('DKK', Currencies::isValidISO4217('208'));
        // Zero-padded codes coerce too, which matters because AUD is 36, not 036.
        $this->assertSame('AUD', Currencies::isValidISO4217('036'));
    }

    public function testIsValidISO4217RejectsAStringThatIsNotNumeric(): void
    {
        // PHP will not coerce these to int even in weak mode.
        foreach (['', 'abc', '36abc'] as $notNumeric) {
            try {
                Currencies::isValidISO4217($notNumeric);
                $this->fail(sprintf('%s was accepted.', var_export($notNumeric, true)));
            } catch (\TypeError $e) {
                $this->assertStringContainsString('must be of type int', $e->getMessage());
            }
        }
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
