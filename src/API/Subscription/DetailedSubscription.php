<?php
namespace OnPay\API\Subscription;


use OnPay\API\Transaction\SimpleTransaction;
use OnPay\API\Transaction\TransactionHistory;
use OnPay\API\Util\Converter;
use OnPay\API\Util\Link;

class DetailedSubscription extends SimpleSubscription
{
    /**
     * @internal Shall not be used outside the library
     * DetailedSubscription constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->cardBin = isset($data['card_bin']) ? $data['card_bin'] :  null;
        $this->expiryMonth = isset($data['expiry_month']) ? $data['expiry_month'] : null;
        $this->expiryYear = isset($data['expiry_year']) ? $data['expiry_year'] : null;
        $this->ip = isset($data['ip']) ? $data['ip'] : null;

        foreach ($data['history'] as $history) {
            $historyItem = new SubscriptionHistory($history);
            $this->history[] = $historyItem;
        }

        foreach ($data['transactions'] as $transaction) {
            $transactionItem = new SimpleTransaction($transaction);
            $this->transactions[] = $transactionItem;
        }
    }

    /**
     * @var string
     */
    public $cardBin;

    /**
     * @var string
     */
    public $expiryMonth;

    /**
     * @var string
     */
    public $expiryYear;

    /**
     * @var string
     */
    public $ip;

    /**
     * @var TransactionHistory[]
     */
    public $history = [];

    /**
     * @var SimpleTransaction[]
     */
    public $transactions = [];

}
