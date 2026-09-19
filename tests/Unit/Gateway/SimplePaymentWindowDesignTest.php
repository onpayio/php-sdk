<?php

namespace Tests\Unit\Gateway;

use OnPay\API\Gateway\SimplePaymentWindowDesign;
use PHPUnit\Framework\TestCase;

/**
 * Direct coverage for the {@see SimplePaymentWindowDesign} name read.
 *
 * The design-collection fixture always carries a name, so the isset()-false side (the
 * null default) is never exercised there.
 */
class SimplePaymentWindowDesignTest extends TestCase
{
    public function testNameIsReadWhenPresent(): void
    {
        $design = new SimplePaymentWindowDesign(['name' => 'Default']);

        $this->assertSame('Default', $design->name);
    }

    public function testNameDefaultsToNullWhenAbsent(): void
    {
        $design = new SimplePaymentWindowDesign([]);

        $this->assertNull($design->name);
    }
}
