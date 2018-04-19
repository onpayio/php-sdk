<?php
declare(strict_types=1);

namespace OnPay\API;


use OnPay\API\Transaction\DetailedTransaction;
use OnPay\API\Transaction\SimpleTransaction;
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
    public function getTransaction(string $identifier): DetailedTransaction {
        $result = $this->api->get('transaction/' . urlencode($identifier));
        $detailedTransaction = new DetailedTransaction($result);
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
    public function getTransactions($page = null, $pageSize = null, $orderBy = null, $query = null, $status = null, $dateAfter = null, $dateBefore = null) : array {

        $queryString = http_build_query(['page' => $page, 'page_size' => $pageSize, 'order_by' => $orderBy, 'query' => $query, 'status' => $status, 'date_after' => $dateAfter, 'date_before' => $dateBefore]);
        $results = $this->api->get('transaction?' . $queryString);

        $data = [];

        foreach ($results as $result) {
            $transaction = new SimpleTransaction($result);
            $data[] = $transaction;
        }

        return $data;
    }

    /**
     * Capture a transaction
     * @param string $transactionNumber
     * @return DetailedTransaction
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function captureTransaction(string $transactionNumber, int $amount = null) : DetailedTransaction {

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

        $transaction = new DetailedTransaction($result);

        return $transaction;
    }

    /**
     * @param string $transactionNumber
     * @return DetailedTransaction
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cancelTransaction(string $transactionNumber) : DetailedTransaction {
        $result = $this->api->post('transaction/' . $transactionNumber . '/cancel');
        $transaction = new DetailedTransaction($result);
        return $transaction;
    }

}
