<?php

declare(strict_types=1);

namespace OnPay\API\Util;

/**
 * Parses the API's date format into \DateTime.
 *
 * @internal Shall not be used outside the library.
 */
class Converter {
    private function __construct() {
    }

    public static function toDateTimeFromString(string $string): \DateTime|false {
        return \DateTime::createFromFormat('Y-m-d H:i:s', $string, new \DateTimeZone('UTC'));
    }
}
