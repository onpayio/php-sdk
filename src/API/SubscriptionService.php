<?php

declare(strict_types=1);

namespace OnPay\API;


use OnPay\API\Subscription\DetailedSubscription;
use OnPay\API\Subscription\SimpleSubscription;
use OnPay\API\Subscription\SubscriptionCollection;
use OnPay\API\Transaction\DetailedTransaction;
use OnPay\API\Exception\ApiException;
use OnPay\API\Util\DataReader;
use OnPay\API\Util\Pagination;
use OnPay\Http\ApiClient;

final class SubscriptionService
{
    private ApiClient $api;

    /**
     * @internal Should never be called outside library
     * SubscriptionService constructor.
     * @param ApiClient $apiClient
     */
    public function __construct(ApiClient $apiClient)
    {
        $this->api = $apiClient;
    }

    /**
     * Get list of subscriptions
     * @param int|null $page
     * @param int|null $pageSize
     * @param string|null $orderBy
     * @param string|null $query
     * @param string|null $status
     * @param string|null $dateAfter
     * @param string|null $dateBefore
     * @param string $direction
     * @return SubscriptionCollection
     * @throws \OnPay\API\Exception\ConnectionException
     * @throws \OnPay\API\Exception\TokenException
     * @throws \OnPay\API\Exception\ApiException when the API response omits a field the SDK requires
     */
    public function getSubscriptions(?int $page = null, ?int $pageSize = null, ?string $orderBy = null, ?string $query = null, ?string $status = null, ?string $dateAfter = null, ?string $dateBefore = null, string $direction = 'DESC'): SubscriptionCollection  {
        $direction = strtoupper($direction);
        if ($direction !== 'ASC') {
            $direction = 'DESC';
        }
        $queryString = http_build_query(
            [
                'page' => $page,
                'page_size' => $pageSize,
                'order_by' => $orderBy,
                'query' => $query,
                'status' => $status,
                'date_after' => $dateAfter,
                'date_before' => $dateBefore,
                'direction' => $direction
            ]);

        $results = $this->api->get('subscription/?' . $queryString);
        $subscriptions = [];

        $data = DataReader::arrayOr($results, 'data');
        foreach (array_keys($data) as $key) {
            $result = DataReader::arrayOr($data, (string) $key);
            $subscription = new SimpleSubscription($result);
            $subscription->setLinks(DataReader::arrayOr($result, 'links'));
            $subscriptions[] = $subscription;
        }

        $collection = new SubscriptionCollection();
        $collection->subscriptions = $subscriptions;
        $meta = DataReader::arrayOr($results, 'meta');
        $collection->pagination = new Pagination(DataReader::arrayOr($meta, 'pagination'));

        return $collection;
    }

    /**
     * Get specific subscription
     * @param string $subscriptionId
     * @return DetailedSubscription
     * @throws \OnPay\API\Exception\ConnectionException
     * @throws \OnPay\API\Exception\TokenException
     * @throws \OnPay\API\Exception\ApiException when the API response omits a field the SDK requires
     */
    public function getSubscription(string $subscriptionId): DetailedSubscription {
        if (empty($subscriptionId)) {
            throw new ApiException('Subscription ID must be provided');
        }

        $result = $this->api->get('subscription/' . $subscriptionId);
        $subscription = new DetailedSubscription(DataReader::arrayOr($result, 'data'));
        $subscription->setLinks(DataReader::arrayOr($result, 'links'));

        return $subscription;
    }

    /**
     * Cancel specific subscription
     * @param string $subscriptionId
     * @return DetailedSubscription
     * @throws \OnPay\API\Exception\ConnectionException
     * @throws \OnPay\API\Exception\TokenException
     * @throws \OnPay\API\Exception\ApiException when the API response omits a field the SDK requires
     */
    public function cancelSubscription(string $subscriptionId): DetailedSubscription {
        if (empty($subscriptionId)) {
            throw new ApiException('Subscription ID must be provided');
        }

        $result = $this->api->post('subscription/' . $subscriptionId . '/cancel');
        $subscription = new DetailedSubscription(DataReader::arrayOr($result, 'data'));
        $subscription->setLinks(DataReader::arrayOr($result, 'links'));
        return $subscription;
    }

    /**
     * Create transaction from subscription
     * @param string $uuid
     * @param int $amount
     * @param string $orderId
     * @param bool $surchargeEnabled
     * @param int $surchargeVatRate
     * @return DetailedTransaction
     * @throws \OnPay\API\Exception\ConnectionException
     * @throws \OnPay\API\Exception\TokenException
     * @throws \OnPay\API\Exception\ApiException when the API response omits a field the SDK requires
     */
    public function createTransactionFromSubscription(string $uuid, int $amount, string $orderId, bool $surchargeEnabled = false, int $surchargeVatRate = 0): DetailedTransaction {
        if (empty($uuid)) {
            throw new ApiException('Subscription UUID must be provided');
        }

        $json = [
            'data' => [
                'amount' => $amount,
                'order_id' => $orderId,
                'surcharge_enabled' => $surchargeEnabled,
                'surcharge_vat_rate' => $surchargeVatRate,
            ],
        ];

        $result = $this->api->post('subscription/' . $uuid . '/authorize', $json);

        $transaction = new DetailedTransaction(DataReader::arrayOr($result, 'data'));
        $transaction->setLinks(DataReader::arrayOr($result, 'links'));

        return $transaction;
    }

}
