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

    public function testRedactsAuthorizationHeaderCaseInsensitivelyAndKeepsTheRest(): void
    {
        $headers = [
            'AUTHORIZATION' => 'Bearer secret',
            'Content-Type' => 'application/json',
            'User-Agent' => 'php-sdk/2.0',
        ];

        $this->assertSame([
            'AUTHORIZATION' => Redactor::REDACTED,
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
            'token_type' => 'Bearer',
            'data' => ['deeper' => ['secret' => 'd', 'CODE' => '1'], 'list' => [['code_verifier' => 'e'], ['ok' => 'f']]],
            'amount' => 100,
            'currency' => 'DKK',
        ]);

        $this->assertSame(
            '{"access_token":"[redacted]","Refresh_Token":"[redacted]","token_type":"Bearer",'
            . '"data":{"deeper":{"secret":"[redacted]","CODE":"[redacted]"},"list":[{"code_verifier":"[redacted]"},{"ok":"f"}]},'
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

    #[DataProvider('ordinaryValues')]
    public function testOrdinaryValuesAreLeftAlone(mixed $value): void
    {
        $this->assertSame(['ref' => $value], $this->redactor->redactData(['ref' => $value]));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function ordinaryValues(): iterable
    {
        yield 'digits' => ['4111111111111111'];
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
