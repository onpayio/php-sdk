<?php
namespace OnPay\API\Transaction;
use OnPay\API\Util\Pagination;
class TransactionCollection
{
    /**
     * @var SimpleTransaction[]
     */
    public $transactions;
    /**
     * @var Pagination
     */
    public $pagination;
}

