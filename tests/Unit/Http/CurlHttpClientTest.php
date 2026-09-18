<?php

namespace Tests\Unit\Http;

use OnPay\OAuth\Client\Http\CurlHttpClient;
use OnPay\OAuth\Client\Http\Exception\CurlException;
use OnPay\OAuth\Client\Http\Request;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Pure-suite coverage for the bundled cURL transport.
 *
 * The suite is deliberately PURE: no php -S server, no loopback sockets, no
 * real network. Every send() below runs on a default (allowHttp=false) client
 * and targets an http:// URL, which libcurl rejects LOCALLY because
 * CURLOPT_PROTOCOLS is restricted to HTTPS -- no DNS lookup, no socket, no
 * egress. This deterministically drives the curl_exec() failure branch.
 *
 * The successful-transfer branch (Response construction) cannot be reached
 * without a real transfer and is left uncovered by design.
 */
class CurlHttpClientTest extends TestCase
{
    public function testConstructWithoutAllowHttpKeyUsesDefault(): void
    {
        // array_key_exists('allowHttp', ...) === false branch: allowHttp keeps
        // its default of false.
        $client = new CurlHttpClient();
        self::assertInstanceOf(CurlHttpClient::class, $client);
        self::assertFalse($this->readAllowHttp($client));
        unset($client);
    }

    public function testConstructCastsTruthyNonBoolToTrue(): void
    {
        // array_key_exists('allowHttp', ...) === true branch + (bool) cast on a
        // non-bool value (a plain `true` would make the cast a no-op).
        // NOTE: never call send() on an allowHttp=true client -- CURLPROTO_HTTP
        // would be permitted and an http:// URL would attempt real egress.
        $client = new CurlHttpClient(['allowHttp' => 1]);
        self::assertTrue($this->readAllowHttp($client));
        unset($client);
    }

    public function testConstructCastsFalsyNonBoolToFalse(): void
    {
        // array_key_exists('allowHttp', ...) === true branch + (bool) cast on a
        // falsy non-bool value.
        $client = new CurlHttpClient(['allowHttp' => 0]);
        self::assertFalse($this->readAllowHttp($client));
        unset($client);
    }

    public function testDestructClosesChannelWithoutError(): void
    {
        $client = new CurlHttpClient();
        // Forces __destruct() while still inside the test body.
        unset($client);
        $this->addToAssertionCount(1);
    }

    public function testSafeStrlenUsesEightBitEncoding(): void
    {
        // Plain ASCII.
        self::assertSame(5, CurlHttpClient::safeStrlen('hello'));
        self::assertSame(0, CurlHttpClient::safeStrlen(''));
        // Multi-byte "é" (U+00E9) is 2 bytes in UTF-8: pins the '8bit' arg.
        self::assertSame(2, CurlHttpClient::safeStrlen("\u{00e9}"));
    }

    public function testSendGetOnDisabledProtocolThrowsCurlException(): void
    {
        // Default client => CURLOPT_PROTOCOLS is HTTPS-only. A GET with no
        // request headers: skips the CURLOPT_POSTFIELDS branch and the
        // request-header loop, then hits the curl_exec failure branch.
        $client = new CurlHttpClient();
        $request = new Request('GET', 'http://example.invalid/x');

        self::assertSame('GET', $request->getMethod());
        self::assertSame('http://example.invalid/x', $request->getUri());
        self::assertNull($request->getBody());
        self::assertSame([], $request->getHeaders());

        try {
            $client->send($request);
            self::fail('expected CurlException');
        } catch (CurlException $e) {
            // sprintf('[%d] %s', curl_errno, curl_error). errno 1 =
            // CURLE_UNSUPPORTED_PROTOCOL. The human text varies by libcurl
            // version, so pin only the format and the errno.
            self::assertMatchesRegularExpression('/^\[1\] .+/', $e->getMessage());
        }

        unset($client);
    }

    public function testSendPostSendsBodyAndRequestHeaders(): void
    {
        // POST => exercises the CURLOPT_POSTFIELDS body branch; supplying
        // request headers exercises the header-array build loop. Still a
        // default (allowHttp=false) client, so it fails locally on http://.
        $client = new CurlHttpClient();
        $request = new Request(
            'POST',
            'http://example.invalid/x',
            ['X-Test' => 'y', 'Content-Type' => 'application/x-www-form-urlencoded'],
            'a=b'
        );

        self::assertSame('POST', $request->getMethod());
        self::assertSame('a=b', $request->getBody());
        self::assertSame(
            ['X-Test' => 'y', 'Content-Type' => 'application/x-www-form-urlencoded'],
            $request->getHeaders()
        );

        try {
            $client->send($request);
            self::fail('expected CurlException');
        } catch (CurlException $e) {
            self::assertMatchesRegularExpression('/^\[1\] .+/', $e->getMessage());
        }

        unset($client);
    }

    public function testResponseHeaderFunctionParsesHeaderWithColon(): void
    {
        $client = new CurlHttpClient();
        // ReflectionMethod::invoke() reaches a private method on PHP 8.1+
        // WITHOUT setAccessible() -- setAccessible is avoided so failOnWarning
        // stays clean.
        $method = new ReflectionMethod($client, 'responseHeaderFunction');

        // Extra surrounding whitespace pins both trim() calls.
        $line = "Content-Type:   application/json\r\n";
        $returned = $method->invoke($client, null, $line);

        self::assertSame(CurlHttpClient::safeStrlen($line), $returned);

        $list = $this->readResponseHeaderList($client);
        self::assertSame('application/json', $list['Content-Type']);

        unset($client);
    }

    public function testResponseHeaderFunctionLaterDuplicateKeyOverwritesEarlier(): void
    {
        $client = new CurlHttpClient();
        $method = new ReflectionMethod($client, 'responseHeaderFunction');

        $method->invoke($client, null, "X-Dup: first\r\n");
        $method->invoke($client, null, "X-Dup: second\r\n");

        $list = $this->readResponseHeaderList($client);
        // Documented behaviour: the later header wins.
        self::assertSame('second', $list['X-Dup']);

        unset($client);
    }

    public function testResponseHeaderFunctionSkipsLineWithoutColon(): void
    {
        $client = new CurlHttpClient();
        $method = new ReflectionMethod($client, 'responseHeaderFunction');

        // Status line has no ':' -> not added to the list, but its byte length
        // is still returned (libcurl requires the full byte count back).
        $line = "HTTP/1.1 200 OK\r\n";
        $returned = $method->invoke($client, null, $line);

        self::assertSame(CurlHttpClient::safeStrlen($line), $returned);
        self::assertSame([], $this->readResponseHeaderList($client));

        unset($client);
    }

    /**
     * @return array<string,string>
     */
    private function readResponseHeaderList(CurlHttpClient $client): array
    {
        // ReflectionProperty::getValue() reads a private property on PHP 8.1+
        // without setAccessible().
        $property = new ReflectionProperty($client, 'responseHeaderList');

        return $property->getValue($client);
    }

    private function readAllowHttp(CurlHttpClient $client): bool
    {
        $property = new ReflectionProperty($client, 'allowHttp');

        return $property->getValue($client);
    }
}
