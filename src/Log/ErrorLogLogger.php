<?php

declare(strict_types=1);

namespace OnPay\Log;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

/**
 * PSR-3 logger that writes to PHP's error_log().
 *
 * This is the logger OnPayAPI falls back to when none is injected, so SDK
 * failures keep surfacing in the web server / PHP error log as they did in 1.x.
 * Records below the minimum level (warning by default) are discarded, which
 * keeps routine debug traffic out of the error log.
 *
 * @internal Shall not be used outside the library.
 */
final class ErrorLogLogger implements LoggerInterface {
    use LoggerTrait;

    private const SEVERITY = [
        LogLevel::DEBUG => 0,
        LogLevel::INFO => 1,
        LogLevel::NOTICE => 2,
        LogLevel::WARNING => 3,
        LogLevel::ERROR => 4,
        LogLevel::CRITICAL => 5,
        LogLevel::ALERT => 6,
        LogLevel::EMERGENCY => 7,
    ];

    private int $minimumSeverity;

    /**
     * @param string $minimumLevel one of the Psr\Log\LogLevel constants
     */
    public function __construct(string $minimumLevel = LogLevel::WARNING) {
        $this->minimumSeverity = self::severityOf($minimumLevel);
    }

    /**
     * @param mixed $level
     * @param string|\Stringable $message
     * @param array<array-key, mixed> $context
     */
    public function log($level, string|\Stringable $message, array $context = []): void {
        if (!\is_string($level)) {
            throw new InvalidArgumentException('Log level must be a string');
        }

        if (self::severityOf($level) < $this->minimumSeverity) {
            return;
        }

        \error_log(\sprintf(
            '[OnPay SDK] %s: %s',
            \strtoupper($level),
            self::format((string) $message, $context)
        ));
    }

    private static function severityOf(string $level): int {
        if (!\array_key_exists($level, self::SEVERITY)) {
            throw new InvalidArgumentException(\sprintf('Unknown log level "%s"', $level));
        }

        return self::SEVERITY[$level];
    }

    /**
     * Interpolates {placeholder}s per PSR-3 and appends any remaining context as JSON.
     *
     * @param array<array-key, mixed> $context
     */
    private static function format(string $message, array $context): string {
        $replacements = [];
        /** @var mixed $value */
        foreach ($context as $key => $value) {
            $placeholder = '{' . $key . '}';
            if (false === \strpos($message, $placeholder)) {
                continue;
            }
            $replacements[$placeholder] = self::stringify($value);
            unset($context[$key]);
        }
        $message = \strtr($message, $replacements);

        if ([] === $context) {
            return $message;
        }

        /** @var mixed $value */
        foreach ($context as $key => $value) {
            if ($value instanceof \Throwable) {
                $context[$key] = \sprintf('%s: %s', \get_class($value), $value->getMessage());
            }
        }

        return $message . ' ' . self::encode($context);
    }

    /**
     * @param mixed $value
     */
    private static function stringify($value): string {
        if ($value instanceof \Throwable) {
            return \sprintf('%s: %s', \get_class($value), $value->getMessage());
        }
        if (\is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }
        if (null === $value) {
            return 'null';
        }

        return self::encode($value);
    }

    /**
     * @param mixed $value
     */
    private static function encode($value): string {
        $json = \json_encode($value, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);

        // @codeCoverageIgnoreStart
        if (false === $json) {
            return '[unencodable]';
        }
        // @codeCoverageIgnoreEnd

        return $json;
    }
}
