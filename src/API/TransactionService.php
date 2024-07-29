<?php

namespace OnPay\API;

use OnPay\API\Exception\ApiException;
use OnPay\API\Transaction\DetailedTransaction;
use OnPay\API\Transaction\SimpleTransaction;
use OnPay\API\Transaction\TransactionCollection;
use OnPay\API\Util\Pagination;
use OnPay\OnPayAPI;

class TransactionService {

    private $api;

    /**
     * @internal Should never be called outside the library
     * TransactionService constructor.
     * @param OnPayAPI $onPayAPI
     */
    public function __construct(OnPayAPI $onPayAPI) {
        $this->api = $onPayAPI;
    }

    /**
     * @param string $identifier
     * @return DetailedTransaction
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransaction($identifier) {
        $result = $this->api->get('transaction/' . urlencode($identifier));

        $detailedTransaction = new DetailedTransaction($result['data']);
        $detailedTransaction->setLinks($result['links']);
        return $detailedTransaction;
    }

    /**
     * @param null $page
     * @param null $pageSize
     * @param null $orderBy
     * @param null $query
     * @param null $status
     * @param null $dateAfter
     * @param null $dateBefore
     * @param string $direction
     * @return TransactionCollection
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransactions($page = null, $pageSize = null, $orderBy = null, $query = null, $status = null, $dateAfter = null, $dateBefore = null, $direction = 'DESC') {
        $direction = strtoupper($direction);
        if ($direction !== 'ASC') {
            $direction = 'DESC';
        }
        $queryString = http_build_query(['page' => $page, 'page_size' => $pageSize, 'order_by' => $orderBy, 'query' => $query, 'status' => $status, 'date_after' => $dateAfter, 'date_before' => $dateBefore, 'direction' => $direction]);
        $results = $this->api->get('transaction/?' . $queryString);

        $transactions = [];

        foreach ($results['data'] as $result) {
            $transaction = new SimpleTransaction($result);
            $transaction->setLinks($result['links']);
            $transactions[] = $transaction;
        }

        $collection = new TransactionCollection();
        $collection->transactions = $transactions;
        $collection->pagination = new Pagination($results['meta']['pagination']);

        return $collection;
    }

    /**
     * Perform Capture of transaction.
     * 
     * $amount and $postActionChargeAmount are mutually exclusive and can not both be used together
     * 
     * Using $amount, the transaction will have the supplied value captured.
     * Using $postActionChargeAmount, this value represents the charged value expected on the transaction after this action has completed. When this value is present the amount captured on the transaction will be automatically calculated to ensure this value is honoured.
     * 
     * If none of the amount parameters are supplied, the entire available amount will be captured.
     * 
     * @param string $transactionNumber
     * @param int|null $amount
     * @param int|null $postActionChargeAmount
     * @return DetailedTransaction
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function captureTransaction($transactionNumber, $amount = null, $postActionChargeAmount = null) {
        $jsonBody = null;

        if(null !== $amount && null !== $postActionChargeAmount) {
            // Both amount parameters not allowed at the same time
            throw new ApiException('$amount and $postActionChargeAmount are mutually exclusive and can not both be used together');
        } else if (null !== $amount) {
            // Amount parameter supplied, add to json body
            $jsonBody = [
                'data' => [
                    'amount' => (int) $amount
                ]
            ];
        } else if (null !== $postActionChargeAmount) {
            // PostActionCaptureAmount parameter supplied, add to json body
            $jsonBody = [
                'data' => [
                    'postActionChargeAmount' => (int) $postActionChargeAmount
                ]
            ];
            
        }

        $result = $this->api->post('transaction/' . $transactionNumber . '/capture', $jsonBody);
        $transaction = new DetailedTransaction($result['data']);
        $transaction->setLinks($result['links']);

        return $transaction;
    }

    /**
     * @param string $transactionNumber
     * @return DetailedTransaction
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cancelTransaction($transactionNumber) {
        $result = $this->api->post('transaction/' . $transactionNumber . '/cancel');
        $transaction = new DetailedTransaction($result['data']);
        $transaction->setLinks($result['links']);
        return $transaction;
    }

    /**
     * Perform refund of transaction.
     * 
     * $amount and $postActionRefundAmount are mutually exclusive and can not both be used together
     * 
     * Using $amount, the transaction will have the supplied value refunded.
     * Using $postActionRefundAmount, this value represents the refunded value expected on the transaction after this action has completed. When this value is present the amount refunded on the transaction will be automatically calculated to ensure this value is honoured.
     * 
     * If none of the amount parameters are supplied, the entire available amount will be refunded.
     * 
     * @param string $transactionNumber
     * @param int|null $amount
     * @param int|null $postActionRefundAmount
     * @return DetailedTransaction
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refundTransaction($transactionNumber, $amount = null, $postActionRefundAmount = null) {
        $jsonBody = null;

        if(null !== $amount && null !== $postActionRefundAmount) {
            // Both amount parameters not allowed at the same time
            throw new ApiException('$amount and $postActionRefundAmount are mutually exclusive and can not both be used together');
        } else if (null !== $amount) {
            // Amount parameter supplied, add to json body
            $jsonBody = [
                'data' => [
                    'amount' => (int) $amount
                ]
            ];
        } else if (null !== $postActionRefundAmount) {
            // PostActionRefundAmount parameter supplied, add to json body
            $jsonBody = [
                'data' => [
                    'postActionRefundAmount' => (int) $postActionRefundAmount
                ]
            ];
        }

        $result = $this->api->post('transaction/' . $transactionNumber . '/refund', $jsonBody);
        $transaction = new DetailedTransaction($result['data']);
        $transaction->setLinks($result['links']);

        return $transaction;
    }
}
