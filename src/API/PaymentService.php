<?php

declare(strict_types=1);

namespace OnPay\API;

use OnPay\API\Exception\InvalidFormatException;
use OnPay\API\Exception\MissingDataException;
use OnPay\API\Payment\SimplePayment;
use OnPay\Http\ApiClient;

final class PaymentService {

    /**
     * @var array<array-key, mixed> Submitted Payment Data
     */
    private array $paymentData = [];
    /**
     * @var string[] An array of fields that must be present for creating new payments
     */
    private array $requiredFields;
    /**
     * @var PaymentWindow|null Holds the data for this payment request
     */
    private ?PaymentWindow $paymentWindow = null;
    private ApiClient $api;

    const CREATE_PAYMENT_API = 'payment/create';

    /**
     * @internal Should never be called outside the library
     * PaymentService constructor.
     * @param ApiClient $apiClient
     */
    public function __construct(ApiClient $apiClient) {
        //Specifically required fields for the create payment endpoint
        $this->requiredFields = [
            "currency",
            "amount",
            "reference",
            "website",
        ];
        $this->api = $apiClient;
    }

    /**
     * @param PaymentWindow $paymentWindow
     * @return SimplePayment
     * @throws Exception\ApiException
     * @throws Exception\ConnectionException
     * @throws Exception\TokenException
     * @throws InvalidFormatException
     * @throws MissingDataException
     */
    public function createNewPayment($paymentWindow): SimplePayment {
        //We can only proceed with this request if we have a valid PaymentWindow Object.
        if (!$paymentWindow instanceof PaymentWindow) {
            throw new InvalidFormatException("Creating a payment request requires a valid PaymentWindow object.");
        }

        // If api has a platform string, and window platform is not modified.
        $apiPlatform = $this->api->getPlatform();
        if (null !== $apiPlatform && $paymentWindow::SDK_VERSION_STRING === $paymentWindow->getPlatform()) {
            $paymentWindow->setPlatform($apiPlatform);
        }

        $this->paymentWindow = $paymentWindow;

        //Use the PaymentWindow and PaymentInfo objects to build the data array.
        $this->buildPaymentDataFromSubmittedFields($this->paymentWindow);

        //Ensure required fields are present.
        $this->validatePaymentData();

        //Build data as array in correct format as required by the API endpoint
        $requestData = $this->buildCreatePaymentData($this->paymentWindow);

        $result = $this->api->post(self::CREATE_PAYMENT_API, $requestData);

        return new SimplePayment($result);
    }

    /**
     * Confirm required fields for a valid payment request are present.
     * @return void
     * @throws MissingDataException
     */
    private function validatePaymentData() {
        $missingData = [];
        foreach ($this->requiredFields as $requiredField) {
            if (!array_key_exists($requiredField, $this->paymentData)) {
                $missingData[] = "Missing required field in payment request: $requiredField";
            }
        }
        if (!empty($missingData)) {
            throw new MissingDataException(implode("\n", $missingData));
        }
    }

    /**
     * Populate submitted fields
     */
    private function buildPaymentDataFromSubmittedFields(PaymentWindow $paymentWindow): void {
        $this->paymentData = $paymentWindow->getAvailableFields();

        $info = $paymentWindow->getInfo();
        if ($info !== null) {
            $this->paymentData = array_merge(
                $this->paymentData,
                $info->getFieldsWithoutPrefix()
            );
        }
        //Remove unnecessary hmac value
        unset($this->paymentData['hmac_sha1']);
    }

    private function buildCreatePaymentData(PaymentWindow $paymentWindow): array {
        $paymentData = [];

        $paymentData['accepturl'] = $this->getPaymentDataByKey('accepturl');
        $paymentData['amount'] = intval($this->getPaymentDataByKey('amount')); // Amount must be int with API, but PaymentWindow historically allows strings.
        $paymentData['callbackurl'] = $this->getPaymentDataByKey('callbackurl');
        $paymentData['currency'] = $this->getPaymentDataByKey('currency');
        $paymentData['declineurl'] = $this->getPaymentDataByKey('declineurl');
        $paymentData['design'] = $this->getPaymentDataByKey('design');
        $paymentData['expiration'] = $this->getPaymentDataByKey('expiration');
        $paymentData['language'] = $this->getPaymentDataByKey('language');
        $paymentData['method'] = $this->getPaymentDataByKey('method');
        $paymentData['platform'] = $this->getPaymentDataByKey('platform');
        $paymentData['reference'] = $this->getPaymentDataByKey('reference');
        $paymentData['testmode'] = boolval($this->getPaymentDataByKey('testmode')); // Testmode must be boolean with API, but PaymentWindow historically allows mixed.
        $paymentData['type'] = $this->getPaymentDataByKey('type');
        $paymentData['create_transaction'] = $this->getPaymentDataByKey('subscription_with_transaction');
        $paymentData['website'] = $this->getPaymentDataByKey('website');
        $paymentData['surcharge_enabled'] = $this->getPaymentDataByKey('surcharge_enabled');
        $paymentData['surcharge_vat_rate'] = $this->getPaymentDataByKey('surcharge_vat_rate');

        $paymentData['info']['address_identical_shipping'] = $this->getPaymentDataByKey('address_identical_shipping');
        $paymentData['info']['delivery_email'] = $this->getPaymentDataByKey('delivery_email');
        $paymentData['info']['delivery_time_frame'] = $this->getPaymentDataByKey('delivery_time_frame');
        $paymentData['info']['email'] = $this->getPaymentDataByKey('email');
        $paymentData['info']['gift_card_amount'] = $this->getPaymentDataByKey('gift_card_amount');
        $paymentData['info']['gift_card_count'] = $this->getPaymentDataByKey('gift_card_count');
        $paymentData['info']['name'] = $this->getPaymentDataByKey('name');
        $paymentData['info']['preorder'] = $this->getPaymentDataByKey('preorder');
        $paymentData['info']['preorder_date'] = $this->getPaymentDataByKey('preorder_date');
        $paymentData['info']['reorder'] = $this->getPaymentDataByKey('reorder');
        $paymentData['info']['shipping_method'] = $this->getPaymentDataByKey('shipping_method');

        $paymentData['info']['account']['id'] = $this->getPaymentDataByKey('account_id');
        $paymentData['info']['account']['date_created'] = $this->getPaymentDataByKey('account_date_created');
        $paymentData['info']['account']['date_change'] = $this->getPaymentDataByKey('account_date_change');
        $paymentData['info']['account']['date_password_change'] = $this->getPaymentDataByKey('account_date_password_change');
        $paymentData['info']['account']['purchases'] = $this->getPaymentDataByKey('account_purchases');
        $paymentData['info']['account']['attempts'] = $this->getPaymentDataByKey('account_attempts');
        $paymentData['info']['account']['shipping_first_use_date'] = $this->getPaymentDataByKey('account_shipping_first_use_date');
        $paymentData['info']['account']['shipping_identical_name'] = $this->getPaymentDataByKey('account_shipping_identical_name');
        $paymentData['info']['account']['suspicious'] = $this->getPaymentDataByKey('account_suspicious');
        $paymentData['info']['account']['attempts_day'] = $this->getPaymentDataByKey('account_attempts_day');
        $paymentData['info']['account']['attempts_year'] = $this->getPaymentDataByKey('account_attempts_year');

        $paymentData['info']['billing']['address_city'] = $this->getPaymentDataByKey('billing_address_city');
        $paymentData['info']['billing']['address_country'] = $this->getPaymentDataByKey('billing_address_country');
        $paymentData['info']['billing']['address_line1'] = $this->getPaymentDataByKey('billing_address_line1');
        $paymentData['info']['billing']['address_line2'] = $this->getPaymentDataByKey('billing_address_line2');
        $paymentData['info']['billing']['address_line3'] = $this->getPaymentDataByKey('billing_address_line3');
        $paymentData['info']['billing']['address_postal_code'] = $this->getPaymentDataByKey('billing_address_postal_code');
        $paymentData['info']['billing']['address_state'] = $this->getPaymentDataByKey('billing_address_state');

        $paymentData['info']['shipping']['address_city'] = $this->getPaymentDataByKey('shipping_address_city');
        $paymentData['info']['shipping']['address_country'] = $this->getPaymentDataByKey('shipping_address_country');
        $paymentData['info']['shipping']['address_line1'] = $this->getPaymentDataByKey('shipping_address_line1');
        $paymentData['info']['shipping']['address_line2'] = $this->getPaymentDataByKey('shipping_address_line2');
        $paymentData['info']['shipping']['address_line3'] = $this->getPaymentDataByKey('shipping_address_line3');
        $paymentData['info']['shipping']['address_postal_code'] = $this->getPaymentDataByKey('shipping_address_postal_code');
        $paymentData['info']['shipping']['address_state'] = $this->getPaymentDataByKey('shipping_address_state');

        $paymentData['info']['phone']['home_cc'] = $this->getPaymentDataByKey('phone_home_cc');
        $paymentData['info']['phone']['home_number'] = $this->getPaymentDataByKey('phone_home_number');
        $paymentData['info']['phone']['mobile_cc'] = $this->getPaymentDataByKey('phone_mobile_cc');
        $paymentData['info']['phone']['mobile_number'] = $this->getPaymentDataByKey('phone_mobile_number');
        $paymentData['info']['phone']['work_cc'] = $this->getPaymentDataByKey('phone_work_cc');
        $paymentData['info']['phone']['work_number'] = $this->getPaymentDataByKey('phone_work_number');


        $cart = $paymentWindow->getCart();
        if (null !== $cart) {
            $paymentData['cart'] = [];
            $paymentData['cart']['shipping'] = $cart->getShipping();
            $paymentData['cart']['handling'] = $cart->getHandling();
            $paymentData['cart']['discount'] = $cart->getDiscount();
            $paymentData['cart']['items'] = [];
            foreach ($cart->getItems() as $item) {
                $paymentData['cart']['items'][] = $item->getFields();
            }

            $cart->getFields();
        }

        return $this->cleanData($paymentData);
    }

    private function cleanData(array $data): array {
        $output = [];
        /** @var mixed $item */
        foreach ($data as $key => $item) {
            if (is_array($item)) {
                $cleaned = $this->cleanData($item);
                if (count($cleaned) > 0) {
                    $output[$key] = $cleaned;
                }
            } else if (is_scalar($item) || is_object($item)) {
                $output[$key] = $item;
            }
        }

        return $output;
    }

    /**
     * @return scalar|array<array-key, mixed>|null
     */
    private function getPaymentDataByKey(string $key) {
        if (!array_key_exists($key, $this->paymentData)) {
            return null;
        }
        return is_scalar($this->paymentData[$key]) || is_array($this->paymentData[$key])
            ? $this->paymentData[$key]
            : null;
    }

}
