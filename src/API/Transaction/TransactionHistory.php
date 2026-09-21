<?php

declare(strict_types=1);

namespace OnPay\API\Transaction;


use OnPay\API\Util\DataReader;

final class TransactionHistory {

    /**
     * @internal Shall not be used outside the library
     * TransactionHistory constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->action = DataReader::requireString($data, 'action');
        $this->amount = DataReader::requireInt($data, 'amount');
        $this->author = DataReader::requireString($data, 'author');
        $this->uuid = DataReader::requireString($data, 'uuid');
        $this->ip = DataReader::requireString($data, 'ip');
        $this->resultCode = DataReader::stringOrNull($data, 'result_code');
        $this->resultText = DataReader::stringOrNull($data, 'result_text');
        $this->successful = DataReader::boolOr($data, 'successful', false);

        $this->dateTime = DataReader::requireDateTime($data, 'date_time');
    }

    public string $action;

    public int $amount;

    public string $author;

    public \DateTime $dateTime;

    public string $ip;

    public string $uuid;

    public ?string $resultCode = null;

    public ?string $resultText = null;

    public bool $successful = false;
}
