<?php

namespace Tests\Unit\Log;

use OnPay\Log\Redactor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RedactorTest extends TestCase
{
    private Redactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new Redactor();
    }

    public function testRedactsCredentialHeadersCaseInsensitivelyAndKeepsTheRest(): void
    {
        $headers = [
            'Authorization' => 'Bearer secret',
            'PROXY-AUTHORIZATION' => 'Basic abc',
            'cookie' => 'session=1',
            'Set-Cookie' => 'session=2',
            'Content-Type' => 'application/json',
            'User-Agent' => 'php-sdk/2.0',
        ];

        $this->assertSame([
            'Authorization' => Redactor::REDACTED,
            'PROXY-AUTHORIZATION' => Redactor::REDACTED,
            'cookie' => Redactor::REDACTED,
            'Set-Cookie' => Redactor::REDACTED,
            'Content-Type' => 'application/json',
            'User-Agent' => 'php-sdk/2.0',
        ], $this->redactor->redactHeaders($headers));
    }

    public function testEmptyAndNullBodiesPassThrough(): void
    {
        $this->assertNull($this->redactor->redactBody(null));
        $this->assertSame('', $this->redactor->redactBody(''));
    }

    public function testRedactsSensitiveJsonKeysRecursivelyAndCaseInsensitively(): void
    {
        $body = json_encode([
            'access_token' => 'a',
            'Refresh_Token' => 'b',
            'client-secret' => 'c',
            'nested' => ['deeper' => ['password' => 'd', 'CVV' => '1'], 'list' => [['pan' => 'e'], ['ok' => 'f']]],
            'amount' => 100,
            'currency' => 'DKK',
        ]);

        $this->assertSame(
            '{"access_token":"[redacted]","Refresh_Token":"[redacted]","client-secret":"[redacted]",'
            . '"nested":{"deeper":{"password":"[redacted]","CVV":"[redacted]"},"list":[{"pan":"[redacted]"},{"ok":"f"}]},'
            . '"amount":100,"currency":"DKK"}',
            $this->redactor->redactBody($body)
        );
    }

    public function testRedactsFormEncodedBodyOnlyWhenContentTypeSaysSo(): void
    {
        $body = 'grant_type=authorization_code&code=abc123&code_verifier=v&client_id=me&redirect_uri=https%3A%2F%2Fx';
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8'];

        $this->assertSame(
            'grant_type=authorization_code&code=%5Bredacted%5D&code_verifier=%5Bredacted%5D&client_id=me&redirect_uri=https%3A%2F%2Fx',
            $this->redactor->redactBody($body, $headers)
        );

        // Without the content type the body is opaque and is dropped rather than guessed at.
        $this->assertSame(
            sprintf('[%d bytes of non-JSON body omitted]', strlen($body)),
            $this->redactor->redactBody($body)
        );
    }

    public function testUnparseableBodyIsOmittedNotLeaked(): void
    {
        $this->assertSame('[12 bytes of non-JSON body omitted]', $this->redactor->redactBody('<html></html'));
        $this->assertSame('[7 bytes of non-JSON body omitted]', $this->redactor->redactBody('"scalar'));
        // A JSON scalar decodes but is not an array: still omitted.
        $this->assertSame('[5 bytes of non-JSON body omitted]', $this->redactor->redactBody('12345'));
    }

    #[DataProvider('cardNumbers')]
    public function testValuesThatLookLikeCardNumbersAreRedactedUnderAnyKey(string $value): void
    {
        $this->assertSame(['ref' => Redactor::REDACTED], $this->redactor->redactData(['ref' => $value]));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function cardNumbers(): iterable
    {
        yield 'visa' => ['4111111111111111'];
        yield 'visa spaced' => ['4111 1111 1111 1111'];
        yield 'mastercard dashed' => ['5555-5555-5555-4444'];
        yield 'amex 15 digits' => ['378282246310005'];
        yield '19 digits' => ['6011111111111111110'];
    }

    #[DataProvider('nonCardNumbers')]
    public function testOrdinaryValuesAreLeftAlone(mixed $value): void
    {
        $this->assertSame(['ref' => $value], $this->redactor->redactData(['ref' => $value]));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function nonCardNumbers(): iterable
    {
        yield 'short digits' => ['123456789012'];
        yield 'too long' => ['41111111111111111111'];
        yield 'fails luhn' => ['4111111111111112'];
        yield 'not digits' => ['order-4111111111111111'];
        yield 'uuid' => ['3f2504e0-4f89-11d3-9a0c-0305e82c3301'];
        yield 'integer amount' => [1000];
        yield 'bool' => [true];
        yield 'null' => [null];
    }

    public function testIntegerKeysAreNeverTreatedAsSensitive(): void
    {
        $this->assertSame([0 => 'a', 1 => ['b']], $this->redactor->redactData([0 => 'a', 1 => ['b']]));
    }
}
