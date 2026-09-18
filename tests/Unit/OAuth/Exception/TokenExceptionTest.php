<?php

namespace Tests\Unit\OAuth\Exception;

use Exception;
use OnPay\OAuth\Client\Exception\OAuthException;
use OnPay\OAuth\Client\Exception\TokenException;
use OnPay\OAuth\Client\Http\Response;
use PHPUnit\Framework\TestCase;

class TokenExceptionTest extends TestCase
{
    public function testMessageAndResponseGetter(): void
    {
        $response = new Response(400, '{"error":"invalid_request"}', ['Content-Type' => 'application/json']);
        $exception = new TokenException('unable to obtain access_token', $response);

        $this->assertSame('unable to obtain access_token', $exception->getMessage());
        $this->assertSame($response, $exception->getResponse());
        $this->assertSame(400, $exception->getResponse()->getStatusCode());
        $this->assertSame(0, $exception->getCode());
        $this->assertInstanceOf(OAuthException::class, $exception);
    }

    public function testCodeAndPreviousArePassedThrough(): void
    {
        $response = new Response(500, '', []);
        $previous = new Exception('root cause');
        $exception = new TokenException('boom', $response, 7, $previous);

        $this->assertSame(7, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
