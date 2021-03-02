<?php
namespace OnPay\API;

use OnPay\API\PaymentWindow\PaymentInfo;

class PaymentWindow
{
    const SDK_VERSION = '1.0.17';

    const METHOD_CARD = 'card';
    const METHOD_MOBILEPAY = 'mobilepay';
    const METHOD_MOBILEPAY_CHECKOUT = 'mobilepay_checkout';
    const METHOD_VIABILL = 'viabill';
    const METHOD_ANYDAY = 'anyday';

    const DELIVERY_DISABLED_NO_REASON = 'no-reason';
    const DELIVERY_DISABLED_NOT_PHYSICAL = 'not-physical';
    const DELIVERY_DISABLED_STORE_PICK_UP = 'store-pick-up';
    const DELIVERY_DISABLED_PARCEL_SHOP_SELECTED = 'parcel-shop-selected';
    const DELIVERY_DISABLED_PARCEL_SHOP_AUTO = 'parcel-shop-auto';

    private $gatewayId;
    private $currency;
    private $amount;
    private $reference;
    private $acceptUrl;
    private $type;
    private $method;
    private $_3dsecure;
    private $language;
    private $declineUrl;
    private $callbackUrl;
    private $design;
    private $testMode;
    private $secret;
    private $delivery_disabled;
    private $subscription_with_transaction;
    private $website;
    private $platform;
    private $expiration;
    /**
     * @var PaymentInfo
     */
    private $info;
    private $availableFields;
    private $requiredFields;
    private $actionUrl = "https://onpay.io/window/v3/";

    /**
     * PaymentWindow constructor.
     */
    public function __construct()
    {
        $this->availableFields = [
            "gatewayId",
            "currency",
            "amount",
            "reference",
            "acceptUrl",
            "type",
            "_3dsecure",
            "language",
            "declineUrl",
            "callbackUrl",
            "design",
            "testMode",
            "method",
            'delivery_disabled',
            'subscription_with_transaction',
            'website',
            'platform',
            'expiration'
        ];

        $this->requiredFields = [
            "gatewayId",
            "currency",
            "reference",
            "acceptUrl",
        ];

        $this->platform = 'php-sdk' . '/' . self::SDK_VERSION;
    }

    /**
     * @param string $gatewayId
     */
    public function setGatewayId($gatewayId)
    {
        $this->gatewayId = $gatewayId;
    }

    /**
     * @return string
     */
    public function getGatewayId() {
        return $this->gatewayId;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getCurrency() {
        return $this->currency;
    }

    /**
     * @param string $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return mixed
     */
    public function getAmount() {
        return $this->amount;
    }

    /**
     * @param string $reference
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    /**
     * @return string
     */
    public function getReference() {
        return $this->reference;
    }

    /**
     * @param string $acceptUrl
     */
    public function setAcceptUrl($acceptUrl)
    {
        $this->acceptUrl = $acceptUrl;
    }

    /**
     * @return string
     */
    public function getAcceptUrl() {
        return $this->acceptUrl;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * @param bool $secureEnabled
     * @deprecated
     */
    public function setSecureEnabled($secureEnabled)
    {
        $this->set3DSecure($secureEnabled);
    }

    /**
     * @return bool
     * @deprecated
     */
    public function hasSecureEnabled() {
        return $this->is3DSecure();
    }

    /**
     * @param bool $threeDs
     */
    public function set3DSecure($threeDs) {
        if ($threeDs) {
            $this->_3dsecure = 'forced';
        } else {
            $this->_3dsecure = null;
        }
    }

    /**
     * @return bool
     */
    public function is3DSecure() {
        return 'forced' === $this->_3dsecure;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * @param mixed $declineUrl
     */
    public function setDeclineUrl($declineUrl)
    {
        $this->declineUrl = $declineUrl;
    }

    /**
     * @return string
     */
    public function getDeclineUrl() {
        return $this->declineUrl;
    }

    /**
     * @param mixed $callbackUrl
     */
    public function setCallbackUrl($callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;
    }

    public function getCallbackUrl() {
        return $this->callbackUrl;
    }

    /**
     * @param mixed $design
     */
    public function setDesign($design)
    {
        $this->design = $design;
    }

    /**
     * @return string
     */
    public function getDesign() {
        return $this->design;
    }

    /**
     * @return string|null
     */
    public function getDeliveryDisabled() {
        return $this->delivery_disabled;
    }

    /**
     * @param string|null $deliveryDisabled
     */
    public function setDeliveryDisabled($deliveryDisabled) {
        $this->delivery_disabled = $deliveryDisabled;
    }

    /**
     * @param string|null $website
     */
    public function setWebsite($website) {
        $this->website = $website;
    }

    /**
     * @return string|null
     */
    public function getWebsite() {
        return $this->website;
    }

    /**
     * @return string
     */
    public function getPlatform() {
        return $this->platform;
    }

    /**
     * Name of platform, version of platform, version of system platform is running on.
     * Concats platform parameters to a / delimited string
     * Examples: 'php-sdk/1/1', 'php-sdk/1', 'php-sdk//1'
     *
     * @param $platform
     * @param null $version
     * @param null $systemVersion
     */
    public function setPlatform($platform, $version = null, $systemVersion = null) {
        $string = $platform;
        if (null !== $version) {
            $string .= '/' . $version;
        }
        if (null !== $systemVersion) {
            if (null === $version) {
                $string .= '/';
            }
            $string .= '/' . $systemVersion;
        }
        $this->platform = $string;
    }

    /**
     * @return int|null
     */
    public function getExpiration() {
        return $this->expiration;
    }

    /**
     * @param int|null $expiration
     */
    public function setExpiration($expiration) {
        $this->expiration = $expiration;
    }

    /**
     * @param mixed $testMode
     */
    public function setTestMode($testMode)
    {
        $this->testMode = $testMode;
    }

    /**
     * @return mixed
     */
    public function getTestMode() {
        return $this->testMode;
    }

    /**
     * @param mixed $secret
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    public function getSecret() {
        return $this->secret;
    }

    /**
     * Generates hmac secret
     * @return string
     */
    public function generateSecret() {

        $fields = $this->getAvailableFields();
        $queryString = strtolower(http_build_query($fields));
        $hmac = hash_hmac('sha1', $queryString, $this->secret);
        return $hmac;
    }

    /**
     * Gets all filled fields
     * @return array
     */
    private function getAvailableFields() {

        $fields = [];

        foreach ($this->availableFields as $field) {
            if (isset($this->info)) {
                $fields = array_merge($fields, $this->info->getFields());
            }
            if(property_exists($this, $field) && null !== $this->{$field}) {
                if (0 === strpos($field, '_')) {
                    $key = 'onpay_' . strtolower(substr($field, 1));
                } else {
                    $key = 'onpay_' . strtolower($field);
                }
                $fields[$key] = $this->{$field};
            }
        }

        ksort($fields);
        return $fields;
    }

    /**
     * Get fields for form
     * @return array
     */
    public function getFormFields() {

        $fields = $this->getAvailableFields();
        $fields['onpay_hmac_sha1'] = $this->generateSecret();
        return $fields;
    }

    /**
     * Checks if the PaymentWindow has the required fields to do a payment
     */
    public function isValid() {
        if ('subscription' !== $this->type && null === $this->amount) {
            return false;
        }
        foreach ($this->requiredFields as $field) {
            if(property_exists($this, $field) && null === $this->{$field}) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns URL to post to
     * @return string
     */
    public function getActionUrl() {
        return $this->actionUrl;
    }


    /**
     * Validate payment
     * @param array $fields
     * @return bool
     */
    public function validatePayment(array $fields) {

        $validFields = [];

        foreach ($fields as $key => $value) {
            if(strpos($key, 'onpay_') !== false) {
                $validFields[$key] = $value;
            }
        }

        $verify = $validFields['onpay_hmac_sha1'];

        unset($validFields['onpay_hmac_sha1']);

        ksort($validFields);

        $queryString = strtolower(http_build_query($validFields));
        $hmac = hash_hmac('sha1', $queryString, $this->secret);

        if($verify === $hmac) {
            return true;
        }

        return false;
    }

    /**
     * Set the PaymentInfo object
     * @param PaymentWindow\PaymentInfo $paymentInfo
     */
    public function setInfo(PaymentWindow\PaymentInfo $paymentInfo) {
        $this->info = $paymentInfo;
    }

    /**
     * @return PaymentInfo|null
     */
    public function getInfo() {
        return $this->info;
    }

    /**
     * @return bool
     */
    public function isSubscriptionWithTransaction() {
        return $this->subscription_with_transaction === '1';
    }

    /**
     * @param bool $subscription_with_transaction
     */
    public function setSubscriptionWithTransaction($subscription_with_transaction) {
        if (true === $subscription_with_transaction) {
            $this->subscription_with_transaction = '1';
        } else {
            $this->subscription_with_transaction = null;
        }
    }
}
