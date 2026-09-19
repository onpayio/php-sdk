<?php

namespace Tests\Unit\Http;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use OnPay\Http\GuzzleClientAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

class GuzzleClientAdapterTest extends TestCase
{
    public function testSendDelegatesToThePsr18ClientIgnoringOptions(): void
    {
        $request = new Request('GET', 'https://api.onpay.invalid/v1/ping');
        $response = new Response(204);

        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('sendRequest')->with($request)->willReturn($response);

        self::assertSame($response, (new GuzzleClientAdapter($client))->send($request, ['timeout' => 1]));
    }

    public function testConfigIsAlwaysNull(): void
    {
        $adapter = new GuzzleClientAdapter($this->createMock(ClientInterface::class));

        self::assertNull($adapter->getConfig());
        self::assertNull($adapter->getConfig('base_uri'));
    }

    public function testSendAsyncIsNotSupported(): void
    {
        $adapter = new GuzzleClientAdapter($this->createMock(ClientInterface::class));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('GuzzleClientAdapter::sendAsync is not supported');
        $adapter->sendAsync(new Request('GET', 'https://api.onpay.invalid'));
    }

    public function testRequestIsNotSupported(): void
    {
        $adapter = new GuzzleClientAdapter($this->createMock(ClientInterface::class));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('GuzzleClientAdapter::request is not supported');
        $adapter->request('GET', 'https://api.onpay.invalid');
    }

    public function testRequestAsyncIsNotSupported(): void
    {
        $adapter = new GuzzleClientAdapter($this->createMock(ClientInterface::class));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('GuzzleClientAdapter::requestAsync is not supported');
        $adapter->requestAsync('GET', 'https://api.onpay.invalid');
    }
}
