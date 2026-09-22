<?php

declare(strict_types=1);

namespace Tests\Unit\Util;

use OnPay\API\Util\Currencies;
use PHPUnit\Framework\TestCase;

/**
 * {@see Currencies::isValidISO4217()} takes an `int`, so a numeric string is rejected
 * rather than quietly failing to match the stored int the way the old `int|string`
 * signature did. Whether the rejection is a TypeError or a coercion is the caller's
 * choice, not the SDK's, so this file declares `strict_types=1` to pin the strict half;
 * {@see CurrenciesTest} pins the weak half.
 */
class CurrenciesStrictTypesTest extends TestCase
{
    public function testIsValidISO4217RejectsANumericStringInStrictMode(): void
    {
        $this->expectException(\TypeError::class);
        /** @psalm-suppress InvalidArgument passing the wrong type is the point of the test */
        Currencies::isValidISO4217('208');
    }

    public function testIsValidISO4217AcceptsAnInt(): void
    {
        $this->assertSame('DKK', Currencies::isValidISO4217(208));
        $this->assertFalse(Currencies::isValidISO4217(999));
    }
}
