<?php

declare(strict_types=1);

namespace OnPay\OAuth\Client;

use OnPay\OAuth\Client\Exception\JsonException;

class Json
{
    public static function encode(mixed $jsonData): string
    {
        try {
            return \json_encode($jsonData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException $e) {
            throw new JsonException($e->getMessage(), 0, $e);
        }
    }

    public static function decode(string $jsonString): mixed
    {
        try {
            return \json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new JsonException($e->getMessage(), 0, $e);
        }
    }
}
