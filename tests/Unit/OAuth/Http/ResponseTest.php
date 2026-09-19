<?php

namespace Tests\Unit\OAuth\Http;

use OnPay\OAuth\Client\Http\Exception\ResponseException;
use OnPay\OAuth\Client\Http\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testConstructorAndBasicGetters(): void
    {
        $response = new Response(200, 'the-body', ['Content-Type' => 'text/plain']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('the-body', $response->getBody());
    }

    public function testHasHeaderIsCaseInsensitive(): void
    {
        $response = new Response(200, '', ['Content-Type' => 'application/json']);

        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertTrue($response->hasHeader('content-type'));
        $this->assertTrue($response->hasHeader('CONTENT-TYPE'));
        $this->assertFalse($response->hasHeader('X-Missing'));
    }

    public function testGetHeaderIsCaseInsensitive(): void
    {
        $response = new Response(200, '', ['Content-Type' => 'application/json']);

        $this->assertSame('application/json', $response->getHeader('content-type'));
        $this->assertSame('application/json', $response->getHeader('CONTENT-TYPE'));
    }

    public function testGetHeaderThrowsWhenMissing(): void
    {
        $response = new Response(200, '', []);

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage('header "X-Missing" not set');
        $response->getHeader('X-Missing');
    }

    public function testJsonDecodesBodyWhenContentTypeIsJson(): void
    {
        $response = new Response(200, '{"a":1}', ['Content-Type' => 'application/json; charset=utf-8']);

        $this->assertSame(['a' => 1], $response->json());
    }

    public function testJsonThrowsWhenContentTypeIsNotJson(): void
    {
        $response = new Response(200, '{"a":1}', ['Content-Type' => 'text/html']);

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage('response MUST have JSON content type');
        $response->json();
    }

    public function testJsonThrowsWhenContentTypeHeaderIsAbsent(): void
    {
        // covers the getHeader('Content-Type') failure branch reached from json()
        $response = new Response(200, '{"a":1}', []);

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage('header "Content-Type" not set');
        $response->json();
    }

    #[DataProvider('okayStatusProvider')]
    public function testIsOkay(int $status, bool $expected): void
    {
        $response = new Response($status, '', []);
        $this->assertSame($expected, $response->isOkay());
    }

    public static function okayStatusProvider(): array
    {
        return [
            '199 not okay' => [199, false],
            '200 okay' => [200, true],
            '299 okay' => [299, true],
            '300 not okay' => [300, false],
            '400 not okay' => [400, false],
            '401 not okay' => [401, false],
        ];
    }

    public function testToStringRendersStatusHeadersAndBody(): void
    {
        $response = new Response(404, 'not-found-body', ['Content-Type' => 'text/plain', 'X-Trace' => 'abc']);

        $string = (string) $response;

        $this->assertStringContainsString('statusCode=404', $string);
        $this->assertStringContainsString('Content-Type: text/plain', $string);
        $this->assertStringContainsString('X-Trace: abc', $string);
        $this->assertStringContainsString('responseBody=not-found-body', $string);
    }
}
