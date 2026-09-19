<?php

namespace Tests\Unit\OAuth\Http;

use OnPay\OAuth\Client\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $request = new Request('PUT', 'https://api.example.com/x', ['X-A' => 'b'], 'the-body');

        $this->assertSame('PUT', $request->getMethod());
        $this->assertSame('https://api.example.com/x', $request->getUri());
        $this->assertSame('the-body', $request->getBody());
        $this->assertSame(['X-A' => 'b'], $request->getHeaders());
    }

    public function testBodyDefaultsToNull(): void
    {
        $request = new Request('GET', 'https://api.example.com/x');
        $this->assertNull($request->getBody());
        $this->assertSame([], $request->getHeaders());
    }

    public function testGetWithoutQueryParameters(): void
    {
        $request = Request::get('https://api.example.com/ping', [], ['Accept' => 'application/json']);

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://api.example.com/ping', $request->getUri());
        $this->assertNull($request->getBody());
        $this->assertSame(['Accept' => 'application/json'], $request->getHeaders());
    }

    public function testGetAppendsQueryParametersWithQuestionMark(): void
    {
        $request = Request::get('https://api.example.com/search', ['q' => 'a b', 'p' => '2']);

        $this->assertSame(
            'https://api.example.com/search?' . \http_build_query(['q' => 'a b', 'p' => '2'], '', '&', PHP_QUERY_RFC3986),
            $request->getUri()
        );
    }

    public function testGetAppendsQueryParametersWithAmpersandWhenUriAlreadyHasQuery(): void
    {
        $request = Request::get('https://api.example.com/search?existing=1', ['q' => 'x']);

        $this->assertSame(
            'https://api.example.com/search?existing=1&' . \http_build_query(['q' => 'x'], '', '&', PHP_QUERY_RFC3986),
            $request->getUri()
        );
    }

    public function testPostBuildsFormUrlEncodedBodyAndContentType(): void
    {
        $request = Request::post('https://api.example.com/token', ['a' => '1', 'b' => 'x y'], ['Accept' => 'application/json']);

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://api.example.com/token', $request->getUri());
        $this->assertSame(\http_build_query(['a' => '1', 'b' => 'x y'], '&'), $request->getBody());
        $this->assertSame([
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], $request->getHeaders());
    }

    public function testPostOverridesCallerSuppliedContentType(): void
    {
        // asserts current behaviour: a caller-provided Content-Type is overwritten by the form-encoded one
        $request = Request::post('https://api.example.com/token', ['a' => '1'], ['Content-Type' => 'application/json']);

        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaders()['Content-Type']);
    }

    public function testSetHeaderAddsAndOverwrites(): void
    {
        $request = new Request('GET', 'https://api.example.com/x', ['X-A' => 'a']);
        $request->setHeader('X-B', 'b');
        $request->setHeader('X-A', 'a2');

        $this->assertSame(['X-A' => 'a2', 'X-B' => 'b'], $request->getHeaders());
    }

    public function testToStringRedactsAuthorizationHeader(): void
    {
        $request = new Request('POST', 'https://api.example.com/x', [
            'Authorization' => 'Bearer super-secret',
            'X-Other' => 'visible',
        ], 'body-data');

        $string = (string) $request;

        $this->assertStringContainsString('requestMethod=POST', $string);
        $this->assertStringContainsString('requestUri=https://api.example.com/x', $string);
        $this->assertStringContainsString('Authorization: XXX-REPLACED-FOR-LOG-XXX', $string);
        $this->assertStringNotContainsString('super-secret', $string);
        $this->assertStringContainsString('X-Other: visible', $string);
        $this->assertStringContainsString('requestBody=body-data', $string);
    }

    public function testToStringLeaksLowercaseAuthorizationHeader(): void
    {
        // asserts current behaviour: redaction is case-sensitive ('Authorization' === $k),
        // so a lowercase 'authorization' header is NOT redacted
        $request = new Request('GET', 'https://api.example.com/x', ['authorization' => 'Bearer leaked-secret']);

        $this->assertStringContainsString('leaked-secret', (string) $request);
    }

    public function testToStringWithNullBodyRendersEmpty(): void
    {
        $request = new Request('GET', 'https://api.example.com/x');
        $this->assertStringContainsString('requestBody=]', (string) $request);
    }
}
