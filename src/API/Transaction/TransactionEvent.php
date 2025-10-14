<?php


namespace OnPay\API\Transaction;


use OnPay\API\Util\Converter;

class TransactionEvent extends TransactionHistory {
    /**
     * @internal Shall not be used outside the library
     * TransactionHistory constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->transaction = isset($data['transaction']) ? $data['transaction'] : null;
        parent::__construct($data);
    }

    /**
     * @var string
     */
    public $transaction;
}