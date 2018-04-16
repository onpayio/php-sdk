<?php
declare(strict_types=1);
namespace OnPay\API;


class PaymentWindow
{
    const METHOD_CARD = 'card';
    const METHOD_MOBILEPAY = 'mobilepay';

    private $gatewayId;
    private $currency;
    private $amount;
    private $reference;
    private $acceptUrl;
    private $type;
    private $method;
    private $secureEnabled;
    private $language;
    private $declineUrl;
    private $callbackUrl;
    private $design;
    private $testMode;
    private $secret;
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
            "secureEnabled",
            "language",
            "declineUrl",
            "callbackUrl",
            "design",
            "testMode",
            "method"
        ];

        $this->requiredFields = [
            "gatewayId",
            "currency",
            "amount",
            "reference",
            "acceptUrl",
        ];
    }

    /**
     * @param string $gatewayId
     */
    public function setGatewayId($gatewayId): void
    {
        $this->gatewayId = $gatewayId;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @param string $amount
     */
    public function setAmount($amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @param string $reference
     */
    public function setReference(string $reference): void
    {
        $this->reference = $reference;
    }

    /**
     * @param string $acceptUrl
     */
    public function setAcceptUrl(string $acceptUrl): void
    {
        $this->acceptUrl = $acceptUrl;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * @param bool $secureEnabled
     */
    public function setSecureEnabled(bool $secureEnabled): void
    {
        if($secureEnabled) {
            $this->secureEnabled = "force";
        } else {
            $secureEnabled = null;
        }
    }

    /**
     * @param mixed $language
     */
    public function setLanguage($language): void
    {
        $this->language = $language;
    }

    /**
     * @param mixed $declineUrl
     */
    public function setDeclineUrl($declineUrl): void
    {
        $this->declineUrl = $declineUrl;
    }

    /**
     * @param mixed $callbackUrl
     */
    public function setCallbackUrl($callbackUrl): void
    {
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * @param mixed $design
     */
    public function setDesign($design): void
    {
        $this->design = $design;
    }

    /**
     * @param mixed $testMode
     */
    public function setTestMode($testMode): void
    {
        $this->testMode = $testMode;
    }


    /**
     * @param mixed $secret
     */
    public function setSecret($secret): void
    {
        $this->secret = $secret;
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
            if(property_exists($this, $field) && null !== $this->{$field}) {
                $key = 'onpay_' . strtolower($field);
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
}
