<?php
namespace OnPay\API;


use OnPay\API\Subscription\DetailedSubscription;
use OnPay\API\Subscription\SimpleSubscription;
use OnPay\API\Subscription\SubscriptionCollection;
use OnPay\API\Transaction\DetailedTransaction;
use OnPay\API\Exception\ApiException;
use OnPay\API\Util\DataReader;
use OnPay\API\Util\Pagination;
use OnPay\OnPayAPI;

class SubscriptionService
{
    private OnPayAPI $api;

    /**
     * @internal Should never be called outside library
     * SubscriptionService constructor.
     * @param OnPayAPI $api
     */
    public function __construct(OnPayAPI $api)
    {
        $this->api = $api;
    }

    /**
     * Get list of subscriptions
     * @param null $page
     * @param null $pageSize
     * @param null $orderBy
     * @param null $query
     * @param null $status
     * @param null $dateAfter
     * @param null $dateBefore
     * @param string $direction
     * @return SubscriptionCollection
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSubscriptions($page = null, $pageSize = null, $orderBy = null, $query = null, $status = null, $dateAfter = null, $dateBefore = null, $direction = 'DESC'): SubscriptionCollection  {
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSubscription($subscriptionId): DetailedSubscription {
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cancelSubscription($subscriptionId): DetailedSubscription {
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createTransactionFromSubscription($uuid, $amount, $orderId, $surchargeEnabled = false, $surchargeVatRate = 0): DetailedTransaction {
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
