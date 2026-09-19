<?php

namespace Tests\Unit\OAuth;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Uri;
use OnPay\OAuth\Psr17RequestFactory;
use PHPUnit\Framework\TestCase;

class Psr17RequestFactoryTest extends TestCase
{
    private Psr17RequestFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $psr17 = new HttpFactory();
        $this->factory = new Psr17RequestFactory($psr17, $psr17);
    }

    public function testBuildsRequestWithHeadersAndProtocolVersion(): void
    {
        $request = $this->factory->getRequest(
            'POST',
            new Uri('https://api.onpay.invalid/oauth2/access_token'),
            ['Accept' => 'application/json', 'X-Multi' => ['a', 'b']],
            null,
            '2.0'
        );

        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://api.onpay.invalid/oauth2/access_token', (string) $request->getUri());
        self::assertSame('2.0', $request->getProtocolVersion());
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
        self::assertSame(['a', 'b'], $request->getHeader('X-Multi'));
        self::assertSame('', (string) $request->getBody());
    }

    public function testStringBodyIsWrappedInAStream(): void
    {
        $request = $this->factory->getRequest('POST', 'https://api.onpay.invalid', [], 'grant_type=refresh_token');

        self::assertSame('grant_type=refresh_token', (string) $request->getBody());
    }

    public function testStreamBodyIsUsedAsIs(): void
    {
        $stream = (new HttpFactory())->createStream('payload');

        $request = $this->factory->getRequest('POST', 'https://api.onpay.invalid', [], $stream);

        self::assertSame($stream, $request->getBody());
    }

    public function testResourceBodyIsWrappedInAStream(): void
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'from-resource');
        rewind($resource);

        $request = $this->factory->getRequest('POST', 'https://api.onpay.invalid', [], $resource);

        self::assertSame('from-resource', (string) $request->getBody());
    }

    public function testInheritedGetRequestWithOptionsRoutesThroughGetRequest(): void
    {
        $request = $this->factory->getRequestWithOptions('POST', 'https://api.onpay.invalid', [
            'headers' => ['Accept' => 'application/json'],
            'body' => 'x=1',
        ]);

        self::assertSame('application/json', $request->getHeaderLine('Accept'));
        self::assertSame('x=1', (string) $request->getBody());
    }
}
