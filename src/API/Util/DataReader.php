<?php

namespace OnPay\API\Util;

/**
 * Typed accessors for decoded JSON payloads: an absent, null or wrong-typed value
 * yields the default, never an error.
 *
 * @internal Shall not be used outside the library.
 */
class DataReader
{
    private function __construct()
    {
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function stringOrNull(array $data, string $key): ?string
    {
        return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : null;
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function intOrNull(array $data, string $key): ?int
    {
        return isset($data[$key]) && is_int($data[$key]) ? $data[$key] : null;
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function boolOrNull(array $data, string $key): ?bool
    {
        return isset($data[$key]) && is_bool($data[$key]) ? $data[$key] : null;
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function boolOr(array $data, string $key, bool $default = false): bool
    {
        return isset($data[$key]) && is_bool($data[$key]) ? $data[$key] : $default;
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>|null
     */
    public static function arrayOrNull(array $data, string $key): ?array
    {
        return isset($data[$key]) && is_array($data[$key]) ? $data[$key] : null;
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    public static function arrayOr(array $data, string $key): array
    {
        return isset($data[$key]) && is_array($data[$key]) ? $data[$key] : [];
    }
}
