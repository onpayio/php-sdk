<?php
declare(strict_types=1);

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

        if(array_key_exists('links', $data)) {
            foreach ($data['links'] as $link) {
                $linkItem = new Link($link);
                $this->links[] = $linkItem;
            }
        }
    }

    /**
     * @internal Shall not be used outside the library
     * @param array $links
     */
    public function setLinks(array $links) {
        foreach ($links as $key=>$link) {
            $linkData = ["rel"=>$key, "uri"=>$link];
            $linkItem = new Link($linkData);

            $this->links[] = $linkItem;
        }
    }

    /**
     * @var string
     */
    public $uuid;
    /**
     * @var bool
     */
    public $threeDs;
    /**
     * @var int
     */
    public $amount;
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
     * @var string
     */
    public $transactionNumber;
    /**
     * @var string
     */
    public $wallet;

    /**
     * @var Link[]
     */
    public $links;
}
