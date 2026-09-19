<?php

namespace Tests\Unit\Log;

use OnPay\Log\ErrorLogLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Captures error_log() output by pointing the error_log ini directive at a temp
 * file for the duration of each test.
 */
class ErrorLogLoggerTest extends TestCase
{
    private string $logFile;

    private string $previousErrorLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logFile = tempnam(sys_get_temp_dir(), 'onpay-sdk-log-');
        $this->previousErrorLog = (string) ini_get('error_log');
        ini_set('error_log', $this->logFile);
    }

    protected function tearDown(): void
    {
        ini_set('error_log', $this->previousErrorLog);
        @unlink($this->logFile);
        parent::tearDown();
    }

    public function testIsAPsr3Logger(): void
    {
        $this->assertInstanceOf(LoggerInterface::class, new ErrorLogLogger());
    }

    public function testWritesWarningAndAboveToErrorLogByDefault(): void
    {
        $logger = new ErrorLogLogger();

        $logger->debug('debug line');
        $logger->info('info line');
        $logger->notice('notice line');
        $logger->warning('warning line');
        $logger->error('error line');
        $logger->critical('critical line');
        $logger->alert('alert line');
        $logger->emergency('emergency line');

        $written = $this->written();
        $this->assertStringNotContainsString('debug line', $written);
        $this->assertStringNotContainsString('info line', $written);
        $this->assertStringNotContainsString('notice line', $written);
        $this->assertStringContainsString('[OnPay SDK] WARNING: warning line', $written);
        $this->assertStringContainsString('[OnPay SDK] ERROR: error line', $written);
        $this->assertStringContainsString('[OnPay SDK] CRITICAL: critical line', $written);
        $this->assertStringContainsString('[OnPay SDK] ALERT: alert line', $written);
        $this->assertStringContainsString('[OnPay SDK] EMERGENCY: emergency line', $written);
    }

    public function testMinimumLevelIsConfigurable(): void
    {
        $logger = new ErrorLogLogger(LogLevel::DEBUG);
        $logger->debug('debug line');

        $this->assertStringContainsString('[OnPay SDK] DEBUG: debug line', $this->written());

        $logger = new ErrorLogLogger(LogLevel::CRITICAL);
        $logger->error('quiet error');

        $this->assertStringNotContainsString('quiet error', $this->written());
    }

    public function testInterpolatesPlaceholdersAndAppendsRemainingContextAsJson(): void
    {
        $logger = new ErrorLogLogger();
        $logger->warning('{method} {uri} -> {status} {missing} {nothing}', [
            'method' => 'GET',
            'uri' => 'https://api.onpay.invalid/v1/ping',
            'status' => 403,
            'nothing' => null,
            'response_body' => '{"errors":[]}',
            'exception' => new \RuntimeException('kaboom'),
        ]);

        $written = $this->written();
        $this->assertStringContainsString(
            'WARNING: GET https://api.onpay.invalid/v1/ping -> 403 {missing} null '
            . '{"response_body":"{\"errors\":[]}","exception":"RuntimeException: kaboom"}',
            $written
        );
    }

    public function testInterpolatesStringableObjectsArraysAndThrowables(): void
    {
        $logger = new ErrorLogLogger();
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringable!';
            }
        };
        $logger->error('{a} {b} {c}', [
            'a' => $stringable,
            'b' => ['x' => 1],
            'c' => new \LogicException('why'),
        ]);

        // Throwables are summarised as "Class: message" — never the full trace with __toString().
        $this->assertMatchesRegularExpression('/ERROR: stringable! \{"x":1\} LogicException: why\R/', $this->written());
    }

    public function testMessageWithoutContextHasNoTrailingJson(): void
    {
        (new ErrorLogLogger())->error('plain');

        $this->assertMatchesRegularExpression('/\[OnPay SDK\] ERROR: plain\R/', $this->written());
    }

    public function testRejectsNonStringLevel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ErrorLogLogger())->log(3, 'nope');
    }

    public function testRejectsUnknownLevel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown log level "verbose"');
        (new ErrorLogLogger())->log('verbose', 'nope');
    }

    public function testRejectsUnknownMinimumLevel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ErrorLogLogger('loud');
    }

    private function written(): string
    {
        return (string) file_get_contents($this->logFile);
    }
}
