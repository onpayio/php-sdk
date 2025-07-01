<?php
namespace OnPay\API;


use OnPay\API\Subscription\DetailedSubscription;
use OnPay\API\Subscription\SimpleSubscription;
use OnPay\API\Subscription\SubscriptionCollection;
use OnPay\API\Transaction\DetailedTransaction;
use OnPay\API\Exception\ApiException;
use OnPay\API\Util\Pagination;
use OnPay\OnPayAPI;

class SubscriptionService
{
    private $api;

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
    public function getSubscriptions($page = null, $pageSize = null, $orderBy = null, $query = null, $status = null, $dateAfter = null, $dateBefore = null, $direction = 'DESC')  {
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

        foreach ($results['data'] as $result) {
            $subscription = new SimpleSubscription($result);
            $subscription->setLinks($result['links']);
            $subscriptions[] = $subscription;
        }

        $collection = new SubscriptionCollection();
        $collection->subscriptions = $subscriptions;
        $collection->pagination = new Pagination($results['meta']['pagination']);

        return $collection;
    }

    /**
     * Get specific subscription
     * @param $subscriptionId
     * @return DetailedSubscription
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSubscription($subscriptionId) {
        if (empty($subscriptionId)) {
            throw new ApiException('Subscription ID must be provided');
        }

        $result = $this->api->get('subscription/' . $subscriptionId);
        $subscription = new DetailedSubscription($result['data']);
        $subscription->setLinks($result['links']);

        return $subscription;
    }

    /**
     * Cancel specific subscription
     * @param $subscriptionId
     * @return DetailedSubscription
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cancelSubscription($subscriptionId) {
        if (empty($subscriptionId)) {
            throw new ApiException('Subscription ID must be provided');
        }

        $result = $this->api->post('subscription/' . $subscriptionId . '/cancel');
        $subscription = new DetailedSubscription($result['data']);
        $subscription->setLinks($result['links']);
        return $subscription;
    }

    /**
     * Create transaction from subscription
     * @param $uuid
     * @param int $amount
     * @param string $orderId
     * @param bool $surchargeEnabled
     * @param int $surchargeVatRate
     * @return DetailedTransaction
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createTransactionFromSubscription($uuid, $amount, $orderId, $surchargeEnabled = false, $surchargeVatRate = 0) {
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

        $transaction = new DetailedTransaction($result['data']);
        $transaction->setLinks($result['links']);

        return $transaction;
    }

}
