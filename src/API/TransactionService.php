<?php

declare(strict_types=1);

namespace OnPay\API;

use OnPay\API\Exception\ApiException;
use OnPay\API\Transaction\DetailedTransaction;
use OnPay\API\Transaction\SimpleTransaction;
use OnPay\API\Transaction\TransactionCollection;
use OnPay\API\Util\DataReader;
use OnPay\API\Util\Pagination;
use OnPay\Http\ApiClient;

class TransactionService {

    private ApiClient $api;

    /**
     * @internal Should never be called outside the library
     * TransactionService constructor.
     * @param ApiClient $apiClient
     */
    public function __construct(ApiClient $apiClient) {
        $this->api = $apiClient;
    }

    /**
     * @param string $identifier
     * @return DetailedTransaction
     * @throws \OnPay\API\Exception\ConnectionException
     * @throws \OnPay\API\Exception\TokenException
     * @throws ApiException when the API response omits a field the SDK requires
     */
    public function getTransaction(string $identifier): DetailedTransaction {
        if (empty($identifier)) {
            throw new ApiException('Transaction number must be provided');
        }
        $result = $this->api->get('transaction/' . urlencode($identifier));

        $detailedTransaction = new DetailedTransaction(DataReader::arrayOr($result, 'data'));
        $detailedTransaction->setLinks(DataReader::arrayOr($result, 'links'));
        return $detailedTransaction;
    }

    /**
     * @param int|null $page
     * @param int|null $pageSize
     * @param string|null $orderBy
     * @param string|null $query
     * @param string|null $status
     * @param string|null $dateAfter
     * @param string|null $dateBefore
     * @param string $direction
     * @return TransactionCollection
     * @throws \OnPay\API\Exception\ConnectionException
     * @throws \OnPay\API\Exception\TokenException
     * @throws ApiException when the API response omits a field the SDK requires
     */
    public function getTransactions(?int $page = null, ?int $pageSize = null, ?string $orderBy = null, ?string $query = null, ?string $status = null, ?string $dateAfter = null, ?string $dateBefore = null, string $direction = 'DESC'): TransactionCollection {
        $direction = strtoupper($direction);
        if ($direction !== 'ASC') {
            $direction = 'DESC';
        }
        $queryString = http_build_query(['page' => $page, 'page_size' => $pageSize, 'order_by' => $orderBy, 'query' => $query, 'status' => $status, 'date_after' => $dateAfter, 'date_before' => $dateBefore, 'direction' => $direction]);
        $results = $this->api->get('transaction/?' . $queryString);

        $transactions = [];

        $data = DataReader::arrayOr($results, 'data');
        foreach (array_keys($data) as $key) {
            $item = is_array($data[$key]) ? $data[$key] : [];
            $transaction = new SimpleTransaction($item);
            $transaction->setLinks(DataReader::arrayOr($item, 'links'));
            $transactions[] = $transaction;
        }

        $collection = new TransactionCollection();
        $collection->transactions = $transactions;
        $collection->pagination = new Pagination(DataReader::arrayOr(DataReader::arrayOr($results, 'meta'), 'pagination'));

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
     * @throws \OnPay\API\Exception\ConnectionException
     * @throws \OnPay\API\Exception\TokenException
     * @throws ApiException when the API response omits a field the SDK requires
     */
    public function captureTransaction(string $transactionNumber, ?int $amount = null, ?int $postActionChargeAmount = null): DetailedTransaction {
        $jsonBody = null;
        if (empty($transactionNumber)) {
            throw new ApiException('Transaction number must be provided');
        }

        if(null !== $amount && null !== $postActionChargeAmount) {
            // Both amount parameters not allowed at the same time
            throw new ApiException('$amount and $postActionChargeAmount are mutually exclusive and can not both be used together');
        } else if (null !== $amount) {
            // Amount parameter supplied, add to json body
            $jsonBody = [
                'data' => [
                    'amount' => $amount
                ]
            ];
        } else if (null !== $postActionChargeAmount) {
            // PostActionCaptureAmount parameter supplied, add to json body
            $jsonBody = [
                'data' => [
                    'postActionChargeAmount' => $postActionChargeAmount
                ]
            ];

        }

        $result = $this->api->post('transaction/' . $transactionNumber . '/capture', $jsonBody);
        $transaction = new DetailedTransaction(DataReader::arrayOr($result, 'data'));
        $transaction->setLinks(DataReader::arrayOr($result, 'links'));

        return $transaction;
    }

    /**
     * @param string $transactionNumber
     * @return DetailedTransaction
     * @throws \OnPay\API\Exception\ConnectionException
     * @throws \OnPay\API\Exception\TokenException
     * @throws ApiException when the API response omits a field the SDK requires
     */
    public function cancelTransaction(string $transactionNumber): DetailedTransaction {
        if (empty($transactionNumber)) {
            throw new ApiException('Transaction number must be provided');
        }
        $result = $this->api->post('transaction/' . $transactionNumber . '/cancel');
        $transaction = new DetailedTransaction(DataReader::arrayOr($result, 'data'));
        $transaction->setLinks(DataReader::arrayOr($result, 'links'));
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
     * @throws \OnPay\API\Exception\ConnectionException
     * @throws \OnPay\API\Exception\TokenException
     * @throws ApiException when the API response omits a field the SDK requires
     */
    public function refundTransaction(string $transactionNumber, ?int $amount = null, ?int $postActionRefundAmount = null): DetailedTransaction {
        $jsonBody = null;

        if (empty($transactionNumber)) {
            throw new ApiException('Transaction number must be provided');
        }
        if(null !== $amount && null !== $postActionRefundAmount) {
            // Both amount parameters not allowed at the same time
            throw new ApiException('$amount and $postActionRefundAmount are mutually exclusive and can not both be used together');
        } else if (null !== $amount) {
            // Amount parameter supplied, add to json body
            $jsonBody = [
                'data' => [
                    'amount' => $amount
                ]
            ];
        } else if (null !== $postActionRefundAmount) {
            // PostActionRefundAmount parameter supplied, add to json body
            $jsonBody = [
                'data' => [
                    'postActionRefundAmount' => $postActionRefundAmount
                ]
            ];
        }

        $result = $this->api->post('transaction/' . $transactionNumber . '/refund', $jsonBody);
        $transaction = new DetailedTransaction(DataReader::arrayOr($result, 'data'));
        $transaction->setLinks(DataReader::arrayOr($result, 'links'));

        return $transaction;
    }
}
