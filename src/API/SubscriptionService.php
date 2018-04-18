<?php
/**
 * Created by PhpStorm.
 * User: mmu
 * Date: 17/04/2018
 * Time: 10.49
 */

namespace OnPay\API;


use OnPay\API\Subscription\DetailedSubscription;
use OnPay\API\Subscription\SimpleSubscription;
use OnPay\API\Transaction\DetailedTransaction;
use OnPay\API\Util\Converter;
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
     * @return array
     */
    public function getSubscriptions($page = null, $pageSize = null, $orderBy = null, $query = null, $status = null, $dateAfter = null, $dateBefore = null) {

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

        $results = $this->api->get('subscription?' . $queryString);

        $subscriptions = [];

        foreach ($results as $result) {
            $subscription = new SimpleSubscription();
            $this->setSimpleSubscription($result, $subscription);
            $subscriptions[] = $subscription;
        }

        return $subscriptions;
    }

    /**
     * Get specific subscription
     * @param $subscriptionId
     * @return DetailedSubscription
     */
    public function getSubscription($subscriptionId) {

        $result = $this->api->get('subscription/' . $subscriptionId);

        $subscription = new DetailedSubscription();
        $this->setDetailedSubscription($result, $subscription);

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

        $subscription = new DetailedSubscription();
        $this->setDetailedSubscription($result, $subscription);

        return $subscription;
    }

    /**
     * @param $uuid
     * @param int $amount
     * @param string $orderId
     * @return DetailedTransaction
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createTransactionFromSubscription($uuid, int $amount, string $orderId) {

        $json = [
            'data' => [
                'amount' => $amount,
                'order_id' => $orderId
            ],
        ];

        $result = $this->api->post('subscription/' . $uuid . '/authorize', $json);

        $transaction = new DetailedTransaction();
        $this->setDetailedTransactionProperties($result, $transaction);

        return $transaction;
    }

    /**
     * @param array $data
     * @param SimpleSubscription $subscription
     */
    private function setSimpleSubscription(array $data, SimpleSubscription $subscription) {
        $subscription->threeDs = $data['3dsecure'] ?? null;
        $subscription->cardType = $data['card_type'] ?? null;
        $subscription->currencyCode = $data['currency_code'] ?? null;
        $subscription->orderId = $data['order_id'] ?? null;
        $subscription->subscriptionNumber = $data['subscription_number'] ?? null;
        $subscription->status = $data['status'] ?? null;
        $subscription->uuid = $data['uuid'] ?? null;
        $subscription->wallet = $data['wallet'] ?? null;
        $subscription->created = Converter::toDateTimeFromString($data['created']) ?? null;
    }

    /**
     * @param array $data
     * @param DetailedSubscription $subscription
     */
    private function setDetailedSubscription(array $data, DetailedSubscription $subscription) {
        $subscription->threeDs = $data['3dsecure'] ?? null;
        $subscription->cardType = $data['card_type'] ?? null;
        $subscription->currencyCode = $data['currency_code'] ?? null;
        $subscription->orderId = $data['order_id'] ?? null;
        $subscription->subscriptionNumber = $data['subscription_number'] ?? null;
        $subscription->status = $data['status'] ?? null;
        $subscription->uuid = $data['uuid'] ?? null;
        $subscription->wallet = $data['wallet'] ?? null;
        $subscription->created = Converter::toDateTimeFromString($data['created']) ?? null;
    }

    // TODO: Remove this part since its dublicated from SubscriptionService

    /**
     * @param array $data
     * @param DetailedTransaction $detailedTransaction
     */
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