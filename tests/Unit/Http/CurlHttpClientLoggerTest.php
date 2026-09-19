<?php

namespace Tests\Unit\Http;

use OnPay\CurlHttpClientLogger;
use OnPay\Http\RecordingHttpClientInterface;
use OnPay\OAuth\Client\Http\CurlHttpClient;
use OnPay\OAuth\Client\Http\Exception\CurlException;
use OnPay\OAuth\Client\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Pure-suite coverage for the bundled recording transport.
 *
 * CurlHttpClientLogger extends CurlHttpClient, so send() inherits the same
 * pure-only constraint: a default (allowHttp=false) client sending http:// is
 * rejected locally by libcurl. send() records lastRequest BEFORE delegating to
 * parent::send(), then throws -- so lastRequest is observable but lastResponse
 * is never assigned on the failure path (that assignment needs a real
 * successful transfer and is left uncovered by design).
 */
class CurlHttpClientLoggerTest extends TestCase
{
    public function testIsARecordingCurlHttpClient(): void
    {
        $logger = new CurlHttpClientLogger();
        self::assertInstanceOf(RecordingHttpClientInterface::class, $logger);
        self::assertInstanceOf(CurlHttpClient::class, $logger);
        unset($logger);
    }

    public function testSendRecordsLastRequestThenThrows(): void
    {
        $logger = new CurlHttpClientLogger();
        $request = new Request('GET', 'http://example.invalid/x', ['X-Trace' => 'abc']);

        try {
            $logger->send($request);
            self::fail('expected CurlException');
        } catch (CurlException $e) {
            self::assertMatchesRegularExpression('/^\[1\] .+/', $e->getMessage());
        }

        // lastRequest is set before parent::send() runs, so it survives the throw.
        $recorded = $logger->getLastRequest();
        self::assertSame($request, $recorded);
        self::assertSame('GET', $recorded->getMethod());
        self::assertSame('http://example.invalid/x', $recorded->getUri());
        self::assertSame(['X-Trace' => 'abc'], $recorded->getHeaders());

        unset($logger);
    }

    public function testGetLastResponseIsNullBeforeAnySuccessfulSend(): void
    {
        // lastResponse is only assigned after a successful parent::send(), which
        // the pure suite cannot produce. getLastResponse() returns the unset
        // property (null) -- the @return Response docblock is not enforced.
        $logger = new CurlHttpClientLogger();
        self::assertNull($logger->getLastResponse());
        unset($logger);
    }
}
