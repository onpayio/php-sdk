<?php

namespace OnPay\API\Subscription;

use OnPay\API\Util\Converter;
use OnPay\API\Util\DataReader;

class SubscriptionHistory
{
    /**
     * @internal Shall not be used outside the library
     * SubscriptionHistory constructor.
     * @param array<array-key, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->action = DataReader::stringOrNull($data, 'action');
        $this->author = DataReader::stringOrNull($data, 'author');
        $this->ip = DataReader::stringOrNull($data, 'ip');
        $this->resultText = DataReader::stringOrNull($data, 'result_text');
        $this->resultCode = DataReader::stringOrNull($data, 'result_code');
        $this->successful = DataReader::boolOr($data, 'successful', false);

        $dateTime = DataReader::stringOrNull($data, 'date_time');
        if ($dateTime !== null) {
            $dateValue = Converter::toDateTimeFromString($dateTime);
            if ($dateValue !== false) {
                $this->date = $dateValue;
            }
        }
    }


    /**
     * @var ?string
     */
    public $action;

    /**
     * @var ?string
     */
    public $author;

    /**
     * @var ?\DateTime
     */
    public $date = null;

    /**
     * @var ?string
     */
    public $ip;

    /**
     * @var ?string
     */
    public $resultCode;

    /**
     * @var ?string
     */
    public $resultText;

    /**
     * @var bool
     */
    public $successful;

}
