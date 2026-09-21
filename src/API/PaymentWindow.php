<?php

declare(strict_types=1);

namespace OnPay\API;

use OnPay\API\Enum\DeliveryDisabled;
use OnPay\API\Enum\PaymentMethod;
use OnPay\API\PaymentWindow\Cart;
use OnPay\API\PaymentWindow\PaymentInfo;
use OnPay\OnPayAPI;

class PaymentWindow
{
    const SDK_VERSION = OnPayAPI::SDK_VERSION;
    const SDK_VERSION_STRING = 'php-sdk' . '/' . OnPayAPI::SDK_VERSION;

    /** @deprecated Use {@see PaymentMethod::CARD} instead. */
    const METHOD_CARD = PaymentMethod::CARD->value;
    /** @deprecated Use {@see PaymentMethod::MOBILEPAY} instead. */
    const METHOD_MOBILEPAY = PaymentMethod::MOBILEPAY->value;
    /** @deprecated Use {@see PaymentMethod::MOBILEPAY_CHECKOUT} instead. */
    const METHOD_MOBILEPAY_CHECKOUT = PaymentMethod::MOBILEPAY_CHECKOUT->value;
    /** @deprecated Use {@see PaymentMethod::VIABILL} instead. */
    const METHOD_VIABILL = PaymentMethod::VIABILL->value;
    /** @deprecated Use {@see PaymentMethod::ANYDAY} instead. */
    const METHOD_ANYDAY = PaymentMethod::ANYDAY->value;
    /** @deprecated Use {@see PaymentMethod::APPLE_PAY} instead. */
    const METHOD_APPLEPAY = PaymentMethod::APPLE_PAY->value;
    /** @deprecated Use {@see PaymentMethod::GOOGLE_PAY} instead. */
    const METHOD_GOOGLEPAY = PaymentMethod::GOOGLE_PAY->value;
    /** @deprecated Use {@see PaymentMethod::VIPPS} instead. */
    const METHOD_VIPPS = PaymentMethod::VIPPS->value;
    /** @deprecated Use {@see PaymentMethod::SWISH} instead. */
    const METHOD_SWISH = PaymentMethod::SWISH->value;
    /** @deprecated Use {@see PaymentMethod::PAYPAL} instead. */
    const METHOD_PAYPAL = PaymentMethod::PAYPAL->value;
    /** @deprecated Use {@see PaymentMethod::KLARNA} instead. */
    const METHOD_KLARNA = PaymentMethod::KLARNA->value;

    /** @deprecated Use {@see DeliveryDisabled::NO_REASON} instead. */
    const DELIVERY_DISABLED_NO_REASON = DeliveryDisabled::NO_REASON->value;
    /** @deprecated Use {@see DeliveryDisabled::NOT_PHYSICAL} instead. */
    const DELIVERY_DISABLED_NOT_PHYSICAL = DeliveryDisabled::NOT_PHYSICAL->value;
    /** @deprecated Use {@see DeliveryDisabled::STORE_PICK_UP} instead. */
    const DELIVERY_DISABLED_STORE_PICK_UP = DeliveryDisabled::STORE_PICK_UP->value;
    /** @deprecated Use {@see DeliveryDisabled::PARCEL_SHOP_SELECTED} instead. */
    const DELIVERY_DISABLED_PARCEL_SHOP_SELECTED = DeliveryDisabled::PARCEL_SHOP_SELECTED->value;
    /** @deprecated Use {@see DeliveryDisabled::PARCEL_SHOP_AUTO} instead. */
    const DELIVERY_DISABLED_PARCEL_SHOP_AUTO = DeliveryDisabled::PARCEL_SHOP_AUTO->value;

    private ?string $gatewayId = null;
    private ?string $currency = null;
    private ?string $amount = null;
    private ?string $reference = null;
    private ?string $acceptUrl = null;
    private ?string $type = null;
    private ?string $method = null;
    private ?string $_3dsecure = null;
    private ?string $language = null;
    private ?string $declineUrl = null;
    private ?string $callbackUrl = null;
    private ?string $design = null;
    /**
     * @var int|bool|string|null
     */
    private $testMode = null;
    private ?string $secret = null;
    private ?string $delivery_disabled = null;
    private ?string $subscription_with_transaction = null;
    private ?string $website = null;
    private ?string $platform = null;
    private ?int $expiration = null;
    private ?PaymentInfo $info = null;
    private ?Cart $cart = null;
    private ?bool $surcharge_enabled = null;
    private ?int $surcharge_vat_rate = null;
    /**
     * @var list<string>
     */
    private $requiredFields;
    private string $actionUrl = "https://onpay.io/window/v3/";

    /**
     * PaymentWindow constructor.
     */
    public function __construct()
    {
        $this->requiredFields = [
            "gatewayId",
            "currency",
            "reference",
            "acceptUrl",
        ];

        $this->platform = self::SDK_VERSION_STRING;
    }

    /**
     * @param string $gatewayId
     */
    public function setGatewayId(string $gatewayId): void
    {
        $this->gatewayId = $gatewayId;
    }

    /**
     * @return string|null
     */
    public function getGatewayId() {
        return $this->gatewayId;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return string|null
     */
    public function getCurrency() {
        return $this->currency;
    }

    /**
     * @param string $amount
     */
    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string|null
     */
    public function getAmount() {
        return $this->amount;
    }

    /**
     * @param string $reference
     */
    public function setReference(string $reference): void
    {
        $this->reference = $reference;
    }

    /**
     * @return string|null
     */
    public function getReference() {
        return $this->reference;
    }

    /**
     * @param string $acceptUrl
     */
    public function setAcceptUrl(string $acceptUrl): void
    {
        $this->acceptUrl = $acceptUrl;
    }

    /**
     * @return string|null
     */
    public function getAcceptUrl() {
        return $this->acceptUrl;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Sets the payment method the window opens with.
     *
     * Accepts a {@see PaymentMethod} case or the raw identifier as a string. Strings are
     * passed through unvalidated, so a method the gateway supports but this SDK does not
     * know yet still works.
     *
     * @param string|PaymentMethod $method
     */
    public function setMethod(string|PaymentMethod $method): void
    {
        $this->method = $method instanceof PaymentMethod ? $method->value : $method;
    }

    /**
     * Returns the raw method identifier as it is sent to the gateway.
     *
     * @return string|null
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * @param bool $secureEnabled
     * @deprecated
     */
    public function setSecureEnabled(bool $secureEnabled): void
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
    public function set3DSecure(bool $threeDs): void {
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
     * @param string $language
     */
    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    /**
     * @return string|null
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * @param string $declineUrl
     */
    public function setDeclineUrl(string $declineUrl): void
    {
        $this->declineUrl = $declineUrl;
    }

    /**
     * @return string|null
     */
    public function getDeclineUrl() {
        return $this->declineUrl;
    }

    /**
     * @param string $callbackUrl
     */
    public function setCallbackUrl(string $callbackUrl): void
    {
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * @return string|null
     */
    public function getCallbackUrl() {
        return $this->callbackUrl;
    }

    /**
     * @param string $design
     */
    public function setDesign(string $design): void
    {
        $this->design = $design;
    }

    /**
     * @return string|null
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
     * Sets the reason delivery-address collection is disabled.
     *
     * Accepts a {@see DeliveryDisabled} case or the raw identifier as a string; null clears
     * the field.
     *
     * @param string|DeliveryDisabled|null $deliveryDisabled
     */
    public function setDeliveryDisabled(string|DeliveryDisabled|null $deliveryDisabled): void {
        $this->delivery_disabled = $deliveryDisabled instanceof DeliveryDisabled
            ? $deliveryDisabled->value
            : $deliveryDisabled;
    }

    /**
     * @param string|null $website
     */
    public function setWebsite(?string $website): void {
        $this->website = $website;
    }

    /**
     * @return string|null
     */
    public function getWebsite() {
        return $this->website;
    }

    /**
     * @return string|null
     */
    public function getPlatform() {
        return $this->platform;
    }

    /**
     * Name of platform, version of platform, version of system platform is running on.
     * Concats platform parameters to a / delimited string
     * Examples: 'php-sdk/1/1', 'php-sdk/1', 'php-sdk//1'
     *
     * @param string $platform
     * @param string|null $version
     * @param string|null $systemVersion
     */
    public function setPlatform(string $platform, ?string $version = null, ?string $systemVersion = null): void {
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
    public function setExpiration(?int $expiration): void {
        $this->expiration = $expiration;
    }

    /**
     * @param int|bool|string|null $testMode
     */
    public function setTestMode($testMode): void
    {
        $this->testMode = $testMode;
    }

    /**
     * @return int|bool|string|null
     */
    public function getTestMode() {
        return $this->testMode;
    }

    /**
     * @param string $secret
     */
    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    /**
     * @return string|null
     */
    public function getSecret() {
        return $this->secret;
    }

    /**
     * Generates hmac secret
     * @return string
     */
    public function generateSecret() {

        $fields = $this->getAvailableFieldsWithPrefix();
        $queryString = strtolower(http_build_query($fields));
        $hmac = hash_hmac('sha1', $queryString, (string) $this->secret);
        return $hmac;
    }

    /**
     * @return array
     * @throws Exception\InvalidCartException
     * @internal For Internal Use Only - Gets all filled fields
     */
    public function getAvailableFields() {
        return $this->buildAvailableFields(false);
    }

    /**
     * Gets all filled fields
     * @return array
     * @throws Exception\InvalidCartException
     */
    private function getAvailableFieldsWithPrefix() {
        return $this->buildAvailableFields();
    }

    /**
     * @param bool $withPrefix
     * @return array
     * @throws Exception\InvalidCartException
     */
    private function buildAvailableFields($withPrefix = true){
        $fields = [];

        if (isset($this->info)) {
            if($withPrefix){
                $fields = array_merge($fields, $this->info->getFields());
            } else {
                $fields = array_merge($fields, $this->info->getFieldsWithoutPrefix());
            }
        }
        if (isset($this->cart)) {
            $this->cart->throwOnInvalid($this->getAmount());
            $fields = array_merge($fields, $this->cart->getFields());
        }
        $values = [
            'gatewayId' => $this->gatewayId,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'reference' => $this->reference,
            'acceptUrl' => $this->acceptUrl,
            'type' => $this->type,
            '_3dsecure' => $this->_3dsecure,
            'language' => $this->language,
            'declineUrl' => $this->declineUrl,
            'callbackUrl' => $this->callbackUrl,
            'design' => $this->design,
            'testMode' => $this->testMode,
            'method' => $this->method,
            'delivery_disabled' => $this->delivery_disabled,
            'subscription_with_transaction' => $this->subscription_with_transaction,
            'website' => $this->website,
            'platform' => $this->platform,
            'expiration' => $this->expiration,
            'surcharge_enabled' => $this->surcharge_enabled,
            'surcharge_vat_rate' => $this->surcharge_vat_rate,
        ];
        foreach ($values as $field => $value) {
            if (null !== $value) {
                $key = '';
                if($withPrefix){
                    $key = 'onpay_';
                }
                if (0 === strpos($field, '_')) {
                    $key .= strtolower(substr($field, 1));
                } else {
                    $key .= strtolower($field);
                }
                $fields[$key] = $value;
            }
        }

        ksort($fields);
        return $fields;
    }

    /**
     * Get fields for form
     * @return array
     * @throws Exception\InvalidCartException
     */
    public function getFormFields() {

        $fields = $this->getAvailableFieldsWithPrefix();
        $fields['onpay_hmac_sha1'] = $this->generateSecret();
        return $fields;
    }

    /**
     * Checks if the PaymentWindow has the required fields to do a payment
     */
    public function isValid(): bool {
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
     * @param array<string, string> $fields
     * @return bool
     */
    public function validatePayment(array $fields): bool {

        $validFields = [];

        foreach ($fields as $key => $value) {
            // Only the fields OnPay signs: those whose key starts with "onpay_".
            // A prefix match (not a substring one) mirrors the server's signing,
            // so a field like "foo_onpay_bar" is correctly excluded.
            if (str_starts_with($key, 'onpay_')) {
                $validFields[$key] = $value;
            }
        }

        if (!isset($validFields['onpay_hmac_sha1'])) {
            return false;
        }

        $verify = $validFields['onpay_hmac_sha1'];

        unset($validFields['onpay_hmac_sha1']);

        ksort($validFields);

        $queryString = strtolower(http_build_query($validFields));
        $hmac = hash_hmac('sha1', $queryString, (string) $this->secret);

        return hash_equals($hmac, $verify);
    }

    /**
     * Set the PaymentInfo object
     * @param PaymentWindow\PaymentInfo $paymentInfo
     */
    public function setInfo(PaymentWindow\PaymentInfo $paymentInfo): void {
        $this->info = $paymentInfo;
    }

    /**
     * Set the Cart object
     *
     * @param Cart|null $cart
     * @return void
     */
    public function setCart(?PaymentWindow\Cart $cart = null): void {
        $this->cart = $cart;
    }

    /**
     * @return Cart|null
     */
    public function getCart() {
        return $this->cart;
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
    public function setSubscriptionWithTransaction(bool $subscription_with_transaction): void {
        if (true === $subscription_with_transaction) {
            $this->subscription_with_transaction = '1';
        } else {
            $this->subscription_with_transaction = null;
        }
    }

    /**
    * @param bool $surcharge_enabled
    */
    public function setSurchargeEnabled(bool $surcharge_enabled): void {
        $this->surcharge_enabled = $surcharge_enabled;
    }

    /**
     * Returns whether surcharge is enabled, or null when the flag was never set.
     */
    public function isSurchargeEnabled(): ?bool {
        return $this->surcharge_enabled;
    }

    /**
     * @return bool|null
     * @deprecated Use {@see PaymentWindow::isSurchargeEnabled()} instead.
     */
    public function isSurcharge_enabled() {
        return $this->isSurchargeEnabled();
    }

    /**
    * @param int $surcharge_vat_rate
    */
    public function setSurchargeVatRate(int $surcharge_vat_rate): void {
        $this->surcharge_vat_rate = $surcharge_vat_rate;
    }

    /**
     * @return int|null
     */
    public function getSurchargeVatRate() {
        return $this->surcharge_vat_rate;
    }
}
