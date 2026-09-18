<?php

namespace Tests\Unit\OAuth\Exception;

use Exception;
use OnPay\OAuth\Client\Exception\AuthorizeException;
use OnPay\OAuth\Client\Exception\OAuthException;
use PHPUnit\Framework\TestCase;

class AuthorizeExceptionTest extends TestCase
{
    public function testMessageAndDescriptionGetter(): void
    {
        $exception = new AuthorizeException('access_denied', 'the user said no');

        $this->assertSame('access_denied', $exception->getMessage());
        $this->assertSame('the user said no', $exception->getDescription());
        $this->assertSame(0, $exception->getCode());
        $this->assertInstanceOf(OAuthException::class, $exception);
    }

    public function testNullDescriptionIsAllowed(): void
    {
        $exception = new AuthorizeException('server_error', null);

        $this->assertSame('server_error', $exception->getMessage());
        $this->assertNull($exception->getDescription());
    }

    public function testCodeAndPreviousArePassedThrough(): void
    {
        $previous = new Exception('root cause');
        $exception = new AuthorizeException('invalid_request', 'bad', 42, $previous);

        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
