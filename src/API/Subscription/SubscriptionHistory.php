<?php

declare(strict_types=1);

namespace OnPay\API\Subscription;

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
        $this->action = DataReader::requireString($data, 'action');
        $this->author = DataReader::requireString($data, 'author');
        $this->uuid = DataReader::requireString($data, 'uuid');
        $this->ip = DataReader::requireString($data, 'ip');
        $this->resultText = DataReader::stringOrNull($data, 'result_text');
        $this->resultCode = DataReader::stringOrNull($data, 'result_code');
        $this->successful = DataReader::boolOr($data, 'successful', false);

        $this->date = DataReader::requireDateTime($data, 'date_time');
    }


    public string $action;

    public string $author;

    public string $uuid;

    public \DateTime $date;

    public string $ip;

    public ?string $resultCode = null;

    public ?string $resultText = null;

    public bool $successful = false;

}
