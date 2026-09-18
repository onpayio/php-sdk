<?php

namespace Tests\Unit\OAuth;

use OnPay\OAuth\Client\Exception\JsonException;
use OnPay\OAuth\Client\Json;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    public function testEncodeArray(): void
    {
        $this->assertSame('{"a":1,"b":"x"}', Json::encode(['a' => 1, 'b' => 'x']));
    }

    public function testEncodeThrowsOnUnencodableValue(): void
    {
        $this->expectException(JsonException::class);
        // NAN cannot be encoded -> json_encode returns false
        Json::encode(NAN);
    }

    public function testEncodeThrowsOnNullBecauseItSerialisesToLiteralNull(): void
    {
        $this->expectException(JsonException::class);
        // asserts current behaviour: encoding null yields the string 'null', which is treated as an error (code 0)
        $this->expectExceptionMessage('unable to encode JSON, error code "0"');
        Json::encode(null);
    }

    public function testDecodeObjectToAssocArray(): void
    {
        $this->assertSame(['a' => 1, 'b' => 'x'], Json::decode('{"a":1,"b":"x"}'));
    }

    public function testDecodeLiteralNullReturnsNullWithoutThrowing(): void
    {
        // asserts current behaviour: valid JSON 'null' decodes to null and does NOT throw
        $this->assertNull(Json::decode('null'));
    }

    public function testDecodeThrowsOnMalformedJson(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('unable to decode JSON, error code');
        Json::decode('{not valid json');
    }
}
