<?php

namespace OnPay\API;


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
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransactions($page = null, $pageSize = null, $orderBy = null, $query = null, $status = null, $dateAfter = null, $dateBefore = null) {

        $queryString = http_build_query(['page' => $page, 'page_size' => $pageSize, 'order_by' => $orderBy, 'query' => $query, 'status' => $status, 'date_after' => $dateAfter, 'date_before' => $dateBefore]);
        $results = $this->api->get('transaction/?' . $queryString);

        $transactions = [];

        foreach ($results['data'] as $result) {
            $transaction = new SimpleTransaction($result);

            $transaction->setLinks($result['links']);
            $transactions[] = new SimpleTransaction($result);
        }

        $collection = new TransactionCollection();
        $collection->transactions = $transactions;
        $collection->pagination = new Pagination($results['meta']['pagination']);

        return $collection;
    }

    /**
     * Capture a transaction
     * @param string $transactionNumber
     * @return DetailedTransaction
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function captureTransaction($transactionNumber, $amount = null) {

        if(null === $amount) {
            $result = $this->api->post('transaction/' . $transactionNumber . '/capture');
        } else {

            $jsonBody = [
                'data' => [
                    'amount' => (int) $amount
                ]
            ];

            $result = $this->api->post('transaction/' . $transactionNumber . '/capture', $jsonBody);
        }

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
     * @param string $transactionNumber
     * @param int|null $amount
     * @return DetailedTransaction
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refundTransaction($transactionNumber, $amount = null) {

        if(null === $amount) {
            $result = $this->api->post('transaction/' . $transactionNumber . '/refund');
        } else {
            $jsonBody = [
                'data' => [
                    'amount' => (int) $amount
                ]
            ];
            $result = $this->api->post('transaction/' . $transactionNumber . '/refund', $jsonBody);
        }

        $transaction = new DetailedTransaction($result['data']);
        $transaction->setLinks($result['links']);

        return $transaction;
    }
}
