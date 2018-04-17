<?php
declare(strict_types=1);

namespace OnPay\API;


use OnPay\API\Transaction\DetailedTransaction;
use OnPay\API\Transaction\SimpleTransaction;
use OnPay\API\Transaction\TransactionHistory;
use OnPay\API\Util\Converter;
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

        $detailedTransaction = new DetailedTransaction();

        $this->setSimpleTransactionProperties($result, $detailedTransaction);

        $detailedTransaction->acquirer = $result['acquirer'] ?? null;
        $detailedTransaction->cardBin = $result['card_bin'] ?? null;
        $detailedTransaction->expiryMonth = $result['expiry_month'] ?? null;
        $detailedTransaction->expiryYear = $result['expiry_year'] ?? null;
        $detailedTransaction->ip = $result['ip'] ?? null;
        $detailedTransaction->subscriptionUuid = $result['subscription_uuid'] ?? null;

        foreach ($result['history'] as $history) {
            $historyItem = new TransactionHistory();

            $historyItem->uuid = $history['uuid'] ?? null;
            $historyItem->action = $history['action'] ?? null;
            $historyItem->amount = $history['amount'] ?? null;
            $historyItem->author = $history['author'] ?? null;
            if (isset($history['date_time'])) {
                $historyItem->dateTime = Converter::toDateTimeFromString($history['date_time']);
            }
            $historyItem->ip = $history['ip'] ?? null;
            $historyItem->resultCode = $history['result_code'] ?? null;
            $historyItem->resultText = $history['result_text'] ?? null;

            $detailedTransaction->history[] = $historyItem;
        }

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
     */
    public function getTransactions($page = null, $pageSize = null, $orderBy = null, $query = null, $status = null, $dateAfter = null, $dateBefore = null) {

        $queryString = http_build_query(['page' => $page, 'page_size' => $pageSize, 'order_by' => $orderBy, 'query' => $query, 'status' => $status, 'date_after' => $dateAfter, 'date_before' => $dateBefore]);
        $results = $this->api->get('transaction?' . $queryString);

        $data = [];

        foreach ($results as $result) {
            $transaction = new SimpleTransaction();
            $this->setSimpleTransactionProperties($result, $transaction);
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
    public function captureTransaction(string $transactionNumber, int $amount = null) {

        if(null == $amount) {
            $result = $this->api->post('transaction/' . $transactionNumber . '/capture');
        } else {

            $jsonBody = [
                'data' => [
                    'amount' => (int) $amount
                ]
            ];

            $result = $this->api->post('transaction/' . $transactionNumber . '/capture', $jsonBody);
        }

        $transaction = new DetailedTransaction();
        $this->setDetailedTransactionProperties($result, $transaction);

        return $transaction;
    }

    /**
     * @param string $transactionNumber
     * @return DetailedTransaction
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cancelTransaction(string $transactionNumber) {
        $result = $this->api->post('transaction/' . $transactionNumber . '/cancel');
        $transaction = new DetailedTransaction();
        $this->setDetailedTransactionProperties($result, $transaction);
        return $transaction;
    }

    private function setSimpleTransactionProperties(array $data, SimpleTransaction $simpleTransaction) {
        $simpleTransaction->uuid = $data['uuid'] ?? null;
        $simpleTransaction->threeDs = $data['3dsecure'] ?? null;
        $simpleTransaction->amount = $data['amount'] ?? null;
        $simpleTransaction->cardType = $data['card_type'] ?? null;
        $simpleTransaction->charged = $data['charged'] ?? null;
        if (isset($data['created'])) {
            $simpleTransaction->created = Converter::toDateTimeFromString($data['created']);
        }
        $simpleTransaction->currencyCode = $data['currency_code'] ?? null;
        $simpleTransaction->orderId = $data['order_id'] ?? null;
        $simpleTransaction->refunded = $data['refunded'] ?? null;
        $simpleTransaction->status = $data['status'] ?? null;
        $simpleTransaction->transactionNumber = $data['transaction_number'] ?? null;
        $simpleTransaction->wallet = $data['wallet'] ?? null;
    }

    private function setDetailedTransactionProperties(array $data, DetailedTransaction $detailedTransaction) {
        $detailedTransaction->uuid = $data['uuid'] ?? null;
        $detailedTransaction->threeDs = $data['3dsecure'] ?? null;
        $detailedTransaction->amount = $data['amount'] ?? null;
        $detailedTransaction->cardType = $data['card_type'] ?? null;
        $detailedTransaction->charged = $data['charged'] ?? null;
        if (isset($data['created'])) {
            $detailedTransaction->created = Converter::toDateTimeFromString($data['created']);
        }
        $detailedTransaction->currencyCode = $data['currency_code'] ?? null;
        $detailedTransaction->orderId = $data['order_id'] ?? null;
        $detailedTransaction->refunded = $data['refunded'] ?? null;
        $detailedTransaction->status = $data['status'] ?? null;
        $detailedTransaction->transactionNumber = $data['transaction_number'] ?? null;
        $detailedTransaction->wallet = $data['wallet'] ?? null;

    }



}
