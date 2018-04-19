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

        $this->threeDs = $data['3dsecure'] ?? null;
        $this->cardType = $data['card_type'] ?? null;
        $this->currencyCode = $data['currency_code'] ?? null;
        $this->orderId = $data['order_id'] ?? null;
        $this->subscriptionNumber = $data['subscription_number'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->uuid = $data['uuid'] ?? null;
        $this->wallet = $data['wallet'] ?? null;
        $this->created = Converter::toDateTimeFromString($data['created']) ?? null;
        $this->cardBin = $data['card_bin'] ?? null;
        $this->expiryMonth = $data['expiry_month'] ?? null;
        $this->expiryYear = $data['expiry_year'] ?? null;
        $this->ip = $data['ip'] ?? null;

        foreach ($data['history'] as $history) {
            $historyItem = new SubscriptionHistory($history);
            $this->history[] = $historyItem;
        }

        foreach ($data['transactions'] as $transaction) {
            $transactionItem = new SimpleTransaction($transaction);
            $this->transactions[] = $transactionItem;
        }

        foreach ($data['links'] as $link) {
            $linkItem = new Link();
            $linkItem->uri = $link['uri'] ?? null;
            $linkItem->rel = $link['rel'] ?? null;

            $this->links[] = $linkItem;
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
