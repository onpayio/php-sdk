<?php
namespace OnPay\API;


use OnPay\API\Subscription\DetailedSubscription;
use OnPay\API\Subscription\SimpleSubscription;
use OnPay\API\Subscription\SubscriptionCollection;
use OnPay\API\Transaction\DetailedTransaction;
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
     * @return SubscriptionCollection
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSubscriptions($page = null, $pageSize = null, $orderBy = null, $query = null, $status = null, $dateAfter = null, $dateBefore = null)  {

        $queryString = http_build_query(
            [
                'page' => $page,
                'page_size' => $pageSize,
                'order_by' => $orderBy,
                'query' => $query,
                'status' => $status,
                'date_after' => $dateAfter,
                'date_before' => $dateBefore
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
     * @return DetailedTransaction
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createTransactionFromSubscription($uuid, $amount, $orderId) {

        $json = [
            'data' => [
                'amount' => $amount,
                'order_id' => $orderId
            ],
        ];

        $result = $this->api->post('subscription/' . $uuid . '/authorize', $json);

        $transaction = new DetailedTransaction($result['data']);
        $transaction->setLinks($result['links']);

        return $transaction;
    }

}
