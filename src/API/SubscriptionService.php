<?php
namespace OnPay\API;


use OnPay\API\Subscription\DetailedSubscription;
use OnPay\API\Subscription\SimpleSubscription;
use OnPay\API\Transaction\DetailedTransaction;
use OnPay\API\Transaction\TransactionHistory;
use OnPay\API\Util\Converter;
use OnPay\API\Util\Link;
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSubscriptions($page = null, $pageSize = null, $orderBy = null, $query = null, $status = null, $dateAfter = null, $dateBefore = null) : array {

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
     * @throws \GuzzleHttp\Exception\GuzzleException
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
    public function cancelSubscription($subscriptionId) : DetailedSubscription {
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
    public function createTransactionFromSubscription($uuid, int $amount, string $orderId) : DetailedTransaction {

        $json = [
            'data' => [
                'amount' => $amount,
                'order_id' => $orderId
            ],
        ];

        $result = $this->api->post('subscription/' . $uuid . '/authorize', $json);

        $transaction = new DetailedTransaction();
        TransactionService::setDetailedTransactionProperties($result, $transaction);

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

        foreach ($data['links'] as $link) {
            $linkItem = new Link();
            $linkItem->uri = $link['uri'] ?? null;
            $linkItem->rel = $link['rel'] ?? null;
            $subscription->links[] = $linkItem;
        }

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
        $subscription->cardBin = $data['card_bin'] ?? null;
        $subscription->expiryMonth = $data['expiry_month'] ?? null;
        $subscription->expiryYear = $data['expiry_year'] ?? null;
        $subscription->ip = $data['ip'] ?? null;

        foreach ($data['history'] as $history) {
            $historyItem = new TransactionHistory();
            $historyItem->uuid = $history['uuid'] ?? null;
            $historyItem->action = $history['action'] ?? null;
            $historyItem->amount = $history['amount'] ?? null;
            $historyItem->author = $history['author'] ?? null;
            if(isset($history['date_time'])) {
                $historyItem->dateTime = Converter::toDateTimeFromString($history['date_time']);
            }
            $historyItem->ip = $history['ip'] ?? null;
            $historyItem->resultText = $history['result_text'] ?? null;
            $historyItem->resultCode = $history['result_code'] ?? null;

            $subscription->history[] = $historyItem;
        }

        foreach ($data['transactions'] as $transaction) {
            $transactionItem = new Transaction\SimpleTransaction();
            $transactionItem->uuid = $transaction['uuid'] ?? null;
            $transactionItem->threeDs = $transaction['3dsecure'] ?? null;
            $transactionItem->amount = $transaction['amount'] ?? null;
            $transactionItem->cardType = $transaction['card_type'] ?? null;
            $transactionItem->charged = $transaction['charged'] ?? null;

            if (isset($transaction['created'])) {
                $transactionItem->created = Converter::toDateTimeFromString($transaction['created']);
            }

            $transactionItem->currencyCode = $transaction['currency_code'] ?? null;
            $transactionItem->orderId = $transaction['order_id'] ?? null;
            $transactionItem->refunded = $transaction['refunded'] ?? null;
            $transactionItem->status = $transaction['status'] ?? null;
            $transactionItem->transactionNumber = $transaction['transaction_number'] ?? null;
            $transactionItem->wallet = $transaction['wallet'] ?? null;

            $subscription->transactions[] = $transactionItem;
        }


        foreach ($data['links'] as $link) {
            $linkItem = new Link();
            $linkItem->uri = $link['uri'] ?? null;
            $linkItem->rel = $link['rel'] ?? null;

            $subscription->links[] = $linkItem;
        }

    }

}
