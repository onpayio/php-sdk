<?php

namespace Tests\Unit\Util;

use OnPay\API\Util\DataReader;
use PHPUnit\Framework\TestCase;

class DataReaderTest extends TestCase
{
    // --- stringOrNull --------------------------------------------------------

    public function testStringOrNullReturnsValueWhenStringPresent(): void
    {
        $this->assertSame('hello', DataReader::stringOrNull(['k' => 'hello'], 'k'));
    }

    public function testStringOrNullReturnsNullWhenKeyMissing(): void
    {
        $this->assertNull(DataReader::stringOrNull([], 'k'));
    }

    public function testStringOrNullReturnsNullWhenValueIsNull(): void
    {
        $this->assertNull(DataReader::stringOrNull(['k' => null], 'k'));
    }

    public function testStringOrNullReturnsNullWhenValueWrongType(): void
    {
        $this->assertNull(DataReader::stringOrNull(['k' => 123], 'k'));
    }

    // --- intOrNull -----------------------------------------------------------

    public function testIntOrNullReturnsValueWhenIntPresent(): void
    {
        $this->assertSame(42, DataReader::intOrNull(['k' => 42], 'k'));
    }

    public function testIntOrNullReturnsNullWhenKeyMissing(): void
    {
        $this->assertNull(DataReader::intOrNull([], 'k'));
    }

    public function testIntOrNullReturnsNullWhenValueIsNull(): void
    {
        $this->assertNull(DataReader::intOrNull(['k' => null], 'k'));
    }

    public function testIntOrNullReturnsNullWhenValueWrongType(): void
    {
        // a numeric string is NOT an int
        $this->assertNull(DataReader::intOrNull(['k' => '42'], 'k'));
    }

    // --- boolOrNull ----------------------------------------------------------

    public function testBoolOrNullReturnsValueWhenBoolPresent(): void
    {
        $this->assertFalse(DataReader::boolOrNull(['k' => false], 'k'));
        $this->assertTrue(DataReader::boolOrNull(['k' => true], 'k'));
    }

    public function testBoolOrNullReturnsNullWhenKeyMissing(): void
    {
        $this->assertNull(DataReader::boolOrNull([], 'k'));
    }

    public function testBoolOrNullReturnsNullWhenValueIsNull(): void
    {
        $this->assertNull(DataReader::boolOrNull(['k' => null], 'k'));
    }

    public function testBoolOrNullReturnsNullWhenValueWrongType(): void
    {
        $this->assertNull(DataReader::boolOrNull(['k' => 1], 'k'));
    }

    // --- boolOr --------------------------------------------------------------

    public function testBoolOrReturnsValueWhenBoolPresent(): void
    {
        $this->assertTrue(DataReader::boolOr(['k' => true], 'k'));
        $this->assertFalse(DataReader::boolOr(['k' => false], 'k', true));
    }

    public function testBoolOrReturnsDefaultWhenKeyMissing(): void
    {
        $this->assertFalse(DataReader::boolOr([], 'k'));
        $this->assertTrue(DataReader::boolOr([], 'k', true));
    }

    public function testBoolOrReturnsDefaultWhenValueIsNull(): void
    {
        $this->assertTrue(DataReader::boolOr(['k' => null], 'k', true));
    }

    public function testBoolOrReturnsDefaultWhenValueWrongType(): void
    {
        $this->assertTrue(DataReader::boolOr(['k' => 'yes'], 'k', true));
    }

    // --- arrayOrNull ---------------------------------------------------------

    public function testArrayOrNullReturnsValueWhenArrayPresent(): void
    {
        $this->assertSame(['a' => 1], DataReader::arrayOrNull(['k' => ['a' => 1]], 'k'));
    }

    public function testArrayOrNullReturnsNullWhenKeyMissing(): void
    {
        $this->assertNull(DataReader::arrayOrNull([], 'k'));
    }

    public function testArrayOrNullReturnsNullWhenValueIsNull(): void
    {
        $this->assertNull(DataReader::arrayOrNull(['k' => null], 'k'));
    }

    public function testArrayOrNullReturnsNullWhenValueWrongType(): void
    {
        $this->assertNull(DataReader::arrayOrNull(['k' => 'not-an-array'], 'k'));
    }

    // --- arrayOr -------------------------------------------------------------

    public function testArrayOrReturnsValueWhenArrayPresent(): void
    {
        $this->assertSame(['a' => 1], DataReader::arrayOr(['k' => ['a' => 1]], 'k'));
    }

    public function testArrayOrReturnsEmptyArrayWhenKeyMissing(): void
    {
        $this->assertSame([], DataReader::arrayOr([], 'k'));
    }

    public function testArrayOrReturnsEmptyArrayWhenValueIsNull(): void
    {
        $this->assertSame([], DataReader::arrayOr(['k' => null], 'k'));
    }

    public function testArrayOrReturnsEmptyArrayWhenValueWrongType(): void
    {
        $this->assertSame([], DataReader::arrayOr(['k' => 'not-an-array'], 'k'));
    }

    public function testConstructorIsPrivate(): void
    {
        $reflection = new \ReflectionClass(DataReader::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPrivate());

        $instance = $reflection->newInstanceWithoutConstructor();
        $constructor->invoke($instance);
        $this->assertInstanceOf(DataReader::class, $instance);
    }
}
