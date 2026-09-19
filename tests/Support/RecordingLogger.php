<?php

namespace Tests\Support;

use Psr\Log\AbstractLogger;

/**
 * PSR-3 logger that keeps every record in memory so tests can assert on what the
 * SDK logged — and, more importantly, on what it did not.
 */
final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string, context: array<array-key, mixed>}> */
    public array $records = [];

    /**
     * @param mixed $level
     * @param string|\Stringable $message
     * @param array<array-key, mixed> $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->records[] = [
            'level' => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    /**
     * @return list<array{level: string, message: string, context: array<array-key, mixed>}>
     */
    public function recordsAtLevel(string $level): array
    {
        return array_values(array_filter($this->records, static fn (array $record): bool => $record['level'] === $level));
    }

    /**
     * Every record flattened to a single string (message + JSON-encoded context),
     * for "this secret must appear nowhere" assertions.
     */
    public function dump(): string
    {
        return implode("\n", array_map(
            static fn (array $record): string => $record['level'] . ' ' . $record['message'] . ' ' . json_encode($record['context']),
            $this->records
        ));
    }
}
