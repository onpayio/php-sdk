<?php
declare(strict_types=1);

namespace OnPay\API\Transaction;


use OnPay\API\Util\Converter;
use OnPay\API\Util\Link;

class DetailedTransaction extends SimpleTransaction {

    /**
     * @internal Shall not be used outside the library
     * DetailedTransaction constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->expiryYear = $data['expiry_year'] ?? null;
        $this->expiryMonth = $data['expiry_month'] ?? null;
        $this->acquirer = $data['acquirer'] ?? null;
        $this->cardBin = $data['card_bin'] ?? null;
        $this->ip = $data['ip'] ?? null;

        $this->uuid = $data['uuid'] ?? null;
        $this->threeDs = $data['3dsecure'] ?? null;
        $this->amount = $data['amount'] ?? null;
        $this->cardType = $data['card_type'] ?? null;
        $this->charged = $data['charged'] ?? null;
        if (isset($data['created'])) {
            $this->created = Converter::toDateTimeFromString($data['created']);
        }
        $this->currencyCode = $data['currency_code'] ?? null;
        $this->orderId = $data['order_id'] ?? null;
        $this->refunded = $data['refunded'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->transactionNumber = $data['transaction_number'] ?? null;
        $this->wallet = $data['wallet'] ?? null;
        $this->charged = $data['charged'] ?? null;
        $this->subscriptionUuid = $data['subscription_uuid'] ?? null;


        foreach ($data['history'] as $history) {

            $historyItem = new TransactionHistory();
            $historyItem->uuid = $history['uuid'] ?? null;
            $historyItem->action = $history['action'] ?? null;
            $historyItem->amount = $history['amount'] ?? null;
            $historyItem->author = $history['author'] ?? null;
            if(isset($history['date_time'])) {
                $historyItem->dateTime = Converter::toDateTimeFromString($history['date_time']);
            }
            $historyItem->ip = $history['ip'] ?? null;
            $historyItem->resultText = $history['result_text'];
            $historyItem->resultCode = $history['result_code'];

            $this->history[] = $historyItem;
        }

        foreach ($data['links'] as $link) {

            $linkItem = new Link();
            $linkItem->rel = $link['rel'] ?? null;
            $linkItem->uri = $link['uri'] ?? null;

            $this->links[] = $linkItem;
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

    public $subscriptionNumber;


}
