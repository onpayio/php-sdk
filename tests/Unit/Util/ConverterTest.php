<?php

namespace Tests\Unit\Util;

use OnPay\API\Util\Converter;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for the {@see Converter} date helper.
 */
class ConverterTest extends TestCase
{
    public function testToDateTimeFromStringParsesUtcDateTime(): void
    {
        $dateTime = Converter::toDateTimeFromString('2021-03-04 05:06:07');

        $this->assertInstanceOf(\DateTime::class, $dateTime);
        $this->assertSame('2021-03-04 05:06:07', $dateTime->format('Y-m-d H:i:s'));
        // The third argument to createFromFormat pins the timezone to UTC.
        $this->assertSame('UTC', $dateTime->getTimezone()->getName());
    }

    public function testToDateTimeFromStringReturnsFalseOnGarbageInput(): void
    {
        // A value that does not match 'Y-m-d H:i:s' makes createFromFormat return false.
        $this->assertFalse(Converter::toDateTimeFromString('not-a-date'));
    }

    /**
     * Converter is a utility class whose constructor is private and empty; it is never
     * meant to be instantiated. Invoked here via reflection purely for coverage
     * completeness of the (otherwise unreachable) private constructor.
     */
    public function testConstructorIsPrivate(): void
    {
        $reflection = new \ReflectionClass(Converter::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPrivate());

        $instance = $reflection->newInstanceWithoutConstructor();
        $constructor->invoke($instance);
        $this->assertInstanceOf(Converter::class, $instance);
    }
}
