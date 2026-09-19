<?php


namespace OnPay\API\Transaction;


use OnPay\API\Util\Converter;
use OnPay\API\Util\DataReader;

class TransactionHistory {

    /**
     * @internal Shall not be used outside the library
     * TransactionHistory constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->action = DataReader::stringOrNull($data, 'action');
        $this->amount = DataReader::intOrNull($data, 'amount');
        $this->author = DataReader::stringOrNull($data, 'author');
        $this->ip = DataReader::stringOrNull($data, 'ip');
        $this->resultCode = DataReader::stringOrNull($data, 'result_code');
        $this->resultText = DataReader::stringOrNull($data, 'result_text');
        $this->successful = DataReader::boolOr($data, 'successful', false);

        $dateTime = DataReader::stringOrNull($data, 'date_time');
        if (null !== $dateTime) {
            $dateTimeValue = Converter::toDateTimeFromString($dateTime);
            if (false !== $dateTimeValue) {
                $this->dateTime = $dateTimeValue;
            }
        }
    }

    public ?string $action = null;

    public ?int $amount = null;

    public ?string $author = null;

    public ?\DateTime $dateTime = null;

    public ?string $ip = null;

    public ?string $resultCode = null;

    public ?string $resultText = null;

    public bool $successful = false;
}
