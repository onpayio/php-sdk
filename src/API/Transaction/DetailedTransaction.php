<?php
declare(strict_types=1);

namespace OnPay\API\Transaction;

class DetailedTransaction extends SimpleTransaction {

    /**
     * @internal Shall not be used outside the library
     * DetailedTransaction constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->expiryYear = $data['expiry_year'] ?? null;
        $this->expiryMonth = $data['expiry_month'] ?? null;
        $this->acquirer = $data['acquirer'] ?? null;
        $this->cardBin = $data['card_bin'] ?? null;
        $this->ip = $data['ip'] ?? null;

        $this->subscriptionUuid = $data['subscription_uuid'] ?? null;
        $this->subscriptionNumber = $data['transaction_number'] ?? null;

        foreach ($data['history'] as $history) {
            $historyItem = new TransactionHistory($history);
            $this->history[] = $historyItem;
        }
    }

    /**
     * @var string
     */
    public $acquirer;
    /**
     * @var string
     */
    public $cardBin;
    /**
     * @var int
     */
    public $expiryMonth;
    /**
     * @var int
     */
    public $expiryYear;
    /**
     * @var string
     */
    public $ip;
    /**
     * @var string
     */
    public $subscriptionUuid;
    /**
     * @var TransactionHistory[]
     */
    public $history = [];

    /**
     * @var string
     */
    public $subscriptionNumber;

}
