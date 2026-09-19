<?php

namespace OnPay\OAuth\Client;

use OnPay\OAuth\Client\Exception\JsonException;

class Json
{
    /**
     * @param mixed $jsonData
     *
     * @return string
     */
    public static function encode($jsonData)
    {
        $jsonString = \json_encode($jsonData);
        // 5.5.0 	The return value on failure was changed from null string to FALSE.
        if (false === $jsonString || 'null' === $jsonString) {
            throw new JsonException(\sprintf('unable to encode JSON, error code "%d"', \json_last_error()));
        }

        return $jsonString;
    }

    /**
     * @param string $jsonString
     *
     * @return mixed
     */
    public static function decode($jsonString)
    {
        /** @var mixed $data */
        $data = \json_decode($jsonString, true);
        if (null === $data && JSON_ERROR_NONE !== \json_last_error()) {
            throw new JsonException(\sprintf('unable to decode JSON, error code "%d"', \json_last_error()));
        }

        return $data;
    }
}
