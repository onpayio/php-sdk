<?php

namespace OnPay\API\Transaction;


use OnPay\API\Util\Converter;
use OnPay\API\Util\Link;

class SimpleTransaction {

    /**
     * @internal Shall not be used outside the library
     * SimpleTransaction constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->uuid = (isset($data['uuid'])) ? $data['uuid'] : null;
        $this->threeDs = (isset($data['3dsecure'])) ? $data['3dsecure'] : null;
        $this->acquirer = isset($data['acquirer']) ? $data['acquirer'] :  null;
        $this->amount = (isset($data['amount'])) ? $data['amount'] : null;
        $this->cardType = (isset($data['card_type'])) ? $data['card_type'] : null;
        $this->charged = (isset($data['charged'])) ? $data['charged'] : null;
        if (isset($data['created'])) {
            $this->created = Converter::toDateTimeFromString($data['created']);
        }
        $this->currencyCode = (isset($data['currency_code'])) ? $data['currency_code'] : null;
        $this->orderId = (isset($data['order_id'])) ? $data['order_id'] :  null;
        $this->refunded = (isset($data['refunded'])) ? $data['refunded'] :  null;
        $this->status = (isset($data['status'])) ? $data['status'] :  null;
        $this->transactionNumber = (isset($data['transaction_number'])) ? $data['transaction_number'] :  null;
        $this->wallet = (isset($data['wallet'])) ? $data['wallet'] : null;
        $this->hasCardholderData = isset($data['has_cardholder_data']) ? $data['has_cardholder_data'] : false;
    }

    /**
     * @internal Shall not be used outside the library
     * @param array $links
     */
    public function setLinks(array $links) {
        foreach ($links as $rel => $link) {
            $linkItem = new Link($rel, $link);
            $this->links[] = $linkItem;
        }
    }

    /**
     * @var int
     */
    public $amount;
    /**
     * @var string
     */
    public $acquirer;
    /**
     * @var string
     */
    public $cardType;
    /**
     * @var int
     */
    public $charged;
    /**
     * @var \DateTime
     */
    public $created;
    /**
     * @var string
     */
    public $currencyCode;
    /**
     * @var string
     */
    public $orderId;
    /**
     * @var int
     */
    public $refunded;
    /**
     * @var string
     */
    public $status;
    /**
     * @var bool
     */
    public $threeDs;
    /**
     * @var string
     */
    public $transactionNumber;
    /**
     * @var string
     */
    public $uuid;
    /**
     * @var string
     */
    public $wallet;
    /**
     * @var bool
     */
    public $hasCardholderData;
    /**
     * @var Link[]
     */
    public $links;
}
