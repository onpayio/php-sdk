<?php


namespace OnPay\API\Util;


class Converter {
    private function __construct() {
    }

    public static function toDateTimeFromString(string $string): \DateTime|false {
        return \DateTime::createFromFormat('Y-m-d H:i:s', $string, new \DateTimeZone('UTC'));
    }
}
