<?php


namespace OnPay\API\Util;


class Converter {
    private function __construct() {
    }

    /**
     * @param $string
     * @return bool|\DateTime
     */
    public static function toDateTimeFromString($string) {
        return \DateTime::createFromFormat('Y-m-d H:i:s', $string, new \DateTimeZone('UTC'));
    }
}
