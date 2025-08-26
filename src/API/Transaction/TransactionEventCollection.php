<?php


namespace OnPay\API\Transaction;


class TransactionEventCollection {
    /**
     * @var TransactionEvent[]
     */
    public $events;
    /**
     * @var string
     */
    public $nextCursor;
}