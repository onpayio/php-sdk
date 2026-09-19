<?php

namespace OnPay\API\Util;

use OnPay\API\Exception\ApiException;

/**
 * Typed accessors for decoded JSON payloads.
 *
 * The `*OrNull`/`*Or` accessors treat an absent, null or wrong-typed value as the
 * default and never throw. The `require*` accessors are for fields the API contract
 * guarantees are always present: they throw {@see ApiException} when the value is
 * absent or of the wrong type, so the caller can expose a non-nullable property.
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
     *
     * @throws ApiException when the key is absent or its value is not a string
     */
    public static function requireString(array $data, string $key): string
    {
        $value = self::stringOrNull($data, $key);
        if (null === $value) {
            throw new ApiException(\sprintf('Expected a string value for key "%s" in the API response', $key));
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @throws ApiException when the key is absent or its value is not an int
     */
    public static function requireInt(array $data, string $key): int
    {
        $value = self::intOrNull($data, $key);
        if (null === $value) {
            throw new ApiException(\sprintf('Expected an int value for key "%s" in the API response', $key));
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @throws ApiException when the key is absent or its value is not a bool
     */
    public static function requireBool(array $data, string $key): bool
    {
        $value = self::boolOrNull($data, $key);
        if (null === $value) {
            throw new ApiException(\sprintf('Expected a bool value for key "%s" in the API response', $key));
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @throws ApiException when the key is absent, not a string, or not a valid date-time
     */
    public static function requireDateTime(array $data, string $key): \DateTime
    {
        $raw = self::requireString($data, $key);
        $dateTime = Converter::toDateTimeFromString($raw);
        if (false === $dateTime) {
            throw new ApiException(\sprintf('Expected a valid date-time for key "%s" in the API response, got "%s"', $key, $raw));
        }

        return $dateTime;
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
