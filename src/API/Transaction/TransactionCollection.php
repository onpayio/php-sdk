<?php
namespace OnPay\API\Transaction;
use OnPay\API\Util\Pagination;
class TransactionCollection
{
    /**
     * @var SimpleTransaction[]
     */
    public array $transactions = [];

    public ?Pagination $pagination = null;
}
