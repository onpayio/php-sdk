<?php
namespace OnPay\API\Subscription;


use OnPay\API\Transaction\SimpleTransaction;
use OnPay\API\Util\DataReader;

class DetailedSubscription extends SimpleSubscription
{
    /**
     * @internal Shall not be used outside the library
     * DetailedSubscription constructor.
     * @param array<array-key, mixed> $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->expiryMonth = DataReader::intOrNull($data, 'expiry_month');
        $this->expiryYear = DataReader::intOrNull($data, 'expiry_year');
        $this->cardCountry = DataReader::stringOrNull($data, 'card_country');
        $this->cardBin = DataReader::stringOrNull($data, 'card_bin');
        $this->ip = DataReader::stringOrNull($data, 'ip');
        $this->ipCountry = DataReader::stringOrNull($data, 'ip_country');
        $this->fee = DataReader::intOrNull($data, 'fee');

        $history = DataReader::arrayOr($data, 'history');
        foreach (array_keys($history) as $key) {
            $this->history[] = new SubscriptionHistory(DataReader::arrayOr($history, (string) $key));
        }

        $transactions = DataReader::arrayOr($data, 'transactions');
        foreach (array_keys($transactions) as $key) {
            $this->transactions[] = new SimpleTransaction(DataReader::arrayOr($transactions, (string) $key));
        }
    }
    public ?string $cardBin = null;

    public ?string $cardCountry = null;

    public ?int $expiryMonth = null;

    public ?int $expiryYear = null;

    public ?string $ip = null;

    public ?string $ipCountry = null;

    /**
     * @var SubscriptionHistory[]
     */
    public array $history = [];

    /**
     * @var SimpleTransaction[]
     */
    public array $transactions = [];

    public ?int $fee = null;

}
