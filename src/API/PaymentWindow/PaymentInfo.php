<?php


namespace OnPay\API\PaymentWindow;


use OnPay\API\Exception\InvalidFormatException;

class PaymentInfo {
    const DELIVERY_TIMEFRAME_ELECTRONIC = '01';
    const DELIVERY_TIMEFRAME_SAMEDAY = '02';
    const DELIVERY_TIMEFRAME_OVERNIGHT = '03';
    const DELIVERY_TIMEFRAME_TWODAY = '04';
    const SHIPPING_METHOD_BILLING = '01';
    const SHIPPING_METHOD_VERIFIED_ADDRESS = '02';
    const SHIPPING_METHOD_OTHER_ADDRESS = '03';
    const SHIPPING_METHOD_STORE = '04';
    const SHIPPING_METHOD_DIGITAL = '05';
    const SHIPPING_METHOD_TRAVEL_EVENT = '06';
    const SHIPPING_METHOD_OTHER = '07';

    protected $availableFields;
    /**
     * @var string
     */
    protected $account_id;
    /**
     * @var string
     */
    protected $account_date_created;
    /**
     * @var string
     */
    protected $account_date_change;
    /**
     * @var string
     */
    protected $account_date_password_change;
    /**
     * @var string
     */
    protected $account_purchases;
    /**
     * @var string
     */
    protected $account_attempts;
    /**
     * @var string
     */
    protected $account_shipping_first_use_date;
    /**
     * @var string
     */
    protected $account_shipping_identical_name;
    /**
     * @var string
     */
    protected $account_suspicious;
    /**
     * @var string
     */
    protected $account_attempts_day;
    /**
     * @var string
     */
    protected $account_attempts_year;
    /**
     * @var string
     */
    protected $address_identical_shipping;
    /**
     * @var string
     */
    protected $billing_address_city;
    /**
     * @var string
     */
    protected $billing_address_country;
    /**
     * @var string
     */
    protected $billing_address_line1;
    /**
     * @var string
     */
    protected $billing_address_line2;
    /**
     * @var string
     */
    protected $billing_address_line3;
    /**
     * @var string
     */
    protected $billing_address_postal_code;
    /**
     * @var string
     */
    protected $billing_address_state;
    /**
     * @var string
     */
    protected $shipping_address_city;
    /**
     * @var string
     */
    protected $shipping_address_country;
    /**
     * @var string
     */
    protected $shipping_address_line1;
    /**
     * @var string
     */
    protected $shipping_address_line2;
    /**
     * @var string
     */
    protected $shipping_address_line3;
    /**
     * @var string
     */
    protected $shipping_address_postal_code;
    /**
     * @var string
     */
    protected $shipping_address_state;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $email;
    /**
     * @var string
     */
    protected $phone_home_cc;
    /**
     * @var string
     */
    protected $phone_home_number;
    /**
     * @var string
     */
    protected $phone_mobile_cc;
    /**
     * @var string
     */
    protected $phone_mobile_number;
    /**
     * @var string
     */
    protected $phone_work_cc;
    /**
     * @var string
     */
    protected $phone_work_number;
    /**
     * @var string
     */
    protected $delivery_email;
    /**
     * @var string
     */
    protected $delivery_time_frame;
    /**
     * @var string
     */
    protected $gift_card_amount;
    /**
     * @var string
     */
    protected $gift_card_count;
    /**
     * @var string
     */
    protected $preorder;
    /**
     * @var string
     */
    protected $preorder_date;
    /**
     * @var string
     */
    protected $reorder;
    /**
     * @var string
     */
    protected $shipping_method;

    public function __construct() {
        $this->availableFields = [
            'account_id' => '\w{1,64}',
            'account_date_created' => '[0-9]{4}\-[0-9]{2}\-[0-9]{2}',
            'account_date_change' => '[0-9]{4}\-[0-9]{2}\-[0-9]{2}',
            'account_date_password_change' => '[0-9]{4}\-[0-9]{2}\-[0-9]{2}',
            'account_purchases' => '[0-9]+',
            'account_attempts' => '[0-9]+',
            'account_shipping_first_use_date' => '[0-9]{4}\-[0-9]{2}\-[0-9]{2}',
            'account_shipping_identical_name' => 'Y|N',
            'account_suspicious' => 'Y|N',
            'account_attempts_day' => '[0-9]+',
            'account_attempts_year' => '[0-9]+',
            'address_identical_shipping' => 'Y|N',
            'billing_address_city' => '.{1,50}',
            'billing_address_country' => '[0-9]{3}',
            'billing_address_line1' => '.{1,50}',
            'billing_address_line2' => '.{1,50}',
            'billing_address_line3' => '.{1,50}',
            'billing_address_postal_code' => '\w{1,16}',
            'billing_address_state' => '\w{1,3}',
            'shipping_address_city' => '.{1,50}',
            'shipping_address_country' => '[0-9]{3}',
            'shipping_address_line1' => '.{1,50}',
            'shipping_address_line2' => '.{1,50}',
            'shipping_address_line3' => '.{1,50}',
            'shipping_address_postal_code' => '\w{1,16}',
            'shipping_address_state' => '\w{1,3}',
            'name' => '.{2,45}',
            'email' => '.{1,254}',
            'phone_home_cc' => '[0-9]{1,3}',
            'phone_home_number' => '[0-9]{1,15}',
            'phone_mobile_cc' => '[0-9]{1,3}',
            'phone_mobile_number' => '[0-9]{1,15}',
            'phone_work_cc' => '[0-9]{1,3}',
            'phone_work_number' => '[0-9]{1,15}',
            'delivery_email' => '.{1,254}',
            'delivery_time_frame' => '[0-9]{2}',
            'gift_card_amount' => '[0-9]+',
            'gift_card_count' => '[0-9]+',
            'preorder' => 'Y|N',
            'preorder_date' => '[0-9]{4}\-[0-9]{2}\-[0-9]{2}',
            'reorder' => 'Y|N',
            'shipping_method' => '[0-9]{2}',
        ];
    }

    protected function validateField($name, $value) {
        if (isset($this->availableFields[$name])) {
            if (1 === preg_match('/^' . $this->availableFields[$name] . '$/u', $value)) {
                return true;
            }
            if (null === $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @internal Meant only for internal use
     */
    public function getFields() {
        $fields = [];

        foreach ($this->availableFields as $field => $pattern) {
            if(property_exists($this, $field) && null !== $this->{$field}) {
                if (0 === strpos($field, '_')) {
                    $key = 'onpay_info_' . strtolower(substr($field, 1));
                } else {
                    $key = 'onpay_info_' . strtolower($field);
                }
                $fields[$key] = $this->{$field};
            }
        }

        return $fields;
    }

    /**
     * @param string $account_id
     * @throws InvalidFormatException
     */
    public function setAccountId($account_id) {
        if (!$this->validateField('account_id', $account_id)) {
            throw new InvalidFormatException();
        }
        $this->account_id = $account_id;
    }

    /**
     * @param string $account_date_created
     * @throws InvalidFormatException
     */
    public function setAccountDateCreated($account_date_created) {
        if (!$this->validateField('account_date_created', $account_date_created)) {
            throw new InvalidFormatException();
        }
        $this->account_date_created = $account_date_created;
    }

    /**
     * @param string $account_date_change
     * @throws InvalidFormatException
     */
    public function setAccountDateChange($account_date_change) {
        if (!$this->validateField('account_date_change', $account_date_change)) {
            throw new InvalidFormatException();
        }
        $this->account_date_change = $account_date_change;
    }

    /**
     * @param string $account_date_password_change
     * @throws InvalidFormatException
     */
    public function setAccountDatePasswordChange($account_date_password_change) {
        if (!$this->validateField('account_date_password_change', $account_date_password_change)) {
            throw new InvalidFormatException();
        }
        $this->account_date_password_change = $account_date_password_change;
    }

    /**
     * @param string $account_purchases
     * @throws InvalidFormatException
     */
    public function setAccountPurchases($account_purchases) {
        if (!$this->validateField('account_purchases', $account_purchases)) {
            throw new InvalidFormatException();
        }
        $this->account_purchases = $account_purchases;
    }

    /**
     * @param string $account_attempts
     * @throws InvalidFormatException
     */
    public function setAccountAttempts($account_attempts) {
        if (!$this->validateField('account_attempts', $account_attempts)) {
            throw new InvalidFormatException();
        }
        $this->account_attempts = $account_attempts;
    }

    /**
     * @param string $account_shipping_first_use_date
     * @throws InvalidFormatException
     */
    public function setAccountShippingFirstUseDate($account_shipping_first_use_date) {
        if (!$this->validateField('account_shipping_first_use_date', $account_shipping_first_use_date)) {
            throw new InvalidFormatException();
        }
        $this->account_shipping_first_use_date = $account_shipping_first_use_date;
    }

    /**
     * @param string $account_shipping_identical_name
     * @throws InvalidFormatException
     */
    public function setAccountShippingIdenticalName($account_shipping_identical_name) {
        if (!$this->validateField('account_shipping_identical_name', $account_shipping_identical_name)) {
            throw new InvalidFormatException();
        }
        $this->account_shipping_identical_name = $account_shipping_identical_name;
    }

    /**
     * @param string $account_suspicious
     * @throws InvalidFormatException
     */
    public function setAccountSuspicious($account_suspicious) {
        if (!$this->validateField('account_suspicious', $account_suspicious)) {
            throw new InvalidFormatException();
        }
        $this->account_suspicious = $account_suspicious;
    }

    /**
     * @param string $account_attempts_day
     * @throws InvalidFormatException
     */
    public function setAccountAttemptsDay($account_attempts_day) {
        if (!$this->validateField('account_attempts_day', $account_attempts_day)) {
            throw new InvalidFormatException();
        }
        $this->account_attempts_day = $account_attempts_day;
    }

    /**
     * @param string $account_attempts_year
     * @throws InvalidFormatException
     */
    public function setAccountAttemptsYear($account_attempts_year) {
        if (!$this->validateField('account_attempts_year', $account_attempts_year)) {
            throw new InvalidFormatException();
        }
        $this->account_attempts_year = $account_attempts_year;
    }

    /**
     * @param string $address_identical_shipping
     * @throws InvalidFormatException
     */
    public function setAddressIdenticalShipping($address_identical_shipping) {
        if (!$this->validateField('address_identical_shipping', $address_identical_shipping)) {
            throw new InvalidFormatException();
        }
        $this->address_identical_shipping = $address_identical_shipping;
    }

    /**
     * @param string $billing_address_city
     * @throws InvalidFormatException
     */
    public function setBillingAddressCity($billing_address_city) {
        if (!$this->validateField('billing_address_city', $billing_address_city)) {
            throw new InvalidFormatException();
        }
        $this->billing_address_city = $billing_address_city;
    }

    /**
     * @param string $billing_address_country
     * @throws InvalidFormatException
     */
    public function setBillingAddressCountry($billing_address_country) {
        if (!$this->validateField('billing_address_country', $billing_address_country)) {
            throw new InvalidFormatException();
        }
        $this->billing_address_country = $billing_address_country;
    }

    /**
     * @param string $billing_address_line1
     * @throws InvalidFormatException
     */
    public function setBillingAddressLine1($billing_address_line1) {
        if (!$this->validateField('billing_address_line1', $billing_address_line1)) {
            throw new InvalidFormatException();
        }
        $this->billing_address_line1 = $billing_address_line1;
    }

    /**
     * @param string $billing_address_line2
     * @throws InvalidFormatException
     */
    public function setBillingAddressLine2($billing_address_line2) {
        if (!$this->validateField('billing_address_line2', $billing_address_line2)) {
            throw new InvalidFormatException();
        }
        $this->billing_address_line2 = $billing_address_line2;
    }

    /**
     * @param string $billing_address_line3
     * @throws InvalidFormatException
     */
    public function setBillingAddressLine3($billing_address_line3) {
        if (!$this->validateField('billing_address_line3', $billing_address_line3)) {
            throw new InvalidFormatException();
        }
        $this->billing_address_line3 = $billing_address_line3;
    }

    /**
     * @param string $billing_address_postal_code
     * @throws InvalidFormatException
     */
    public function setBillingAddressPostalCode($billing_address_postal_code) {
        if (!$this->validateField('billing_address_postal_code', $billing_address_postal_code)) {
            throw new InvalidFormatException();
        }
        $this->billing_address_postal_code = $billing_address_postal_code;
    }

    /**
     * @param string $billing_address_state
     * @throws InvalidFormatException
     */
    public function setBillingAddressState($billing_address_state) {
        if (!$this->validateField('billing_address_state', $billing_address_state)) {
            throw new InvalidFormatException();
        }
        $this->billing_address_state = $billing_address_state;
    }

    /**
     * @param string $shipping_address_city
     * @throws InvalidFormatException
     */
    public function setShippingAddressCity($shipping_address_city) {
        if (!$this->validateField('shipping_address_city', $shipping_address_city)) {
            throw new InvalidFormatException();
        }
        $this->shipping_address_city = $shipping_address_city;
    }

    /**
     * @param string $shipping_address_country
     * @throws InvalidFormatException
     */
    public function setShippingAddressCountry($shipping_address_country) {
        if (!$this->validateField('shipping_address_country', $shipping_address_country)) {
            throw new InvalidFormatException();
        }
        $this->shipping_address_country = $shipping_address_country;
    }

    /**
     * @param string $shipping_address_line1
     * @throws InvalidFormatException
     */
    public function setShippingAddressLine1($shipping_address_line1) {
        if (!$this->validateField('shipping_address_line1', $shipping_address_line1)) {
            throw new InvalidFormatException();
        }
        $this->shipping_address_line1 = $shipping_address_line1;
    }

    /**
     * @param string $shipping_address_line2
     * @throws InvalidFormatException
     */
    public function setShippingAddressLine2($shipping_address_line2) {
        if (!$this->validateField('shipping_address_line2', $shipping_address_line2)) {
            throw new InvalidFormatException();
        }
        $this->shipping_address_line2 = $shipping_address_line2;
    }

    /**
     * @param string $shipping_address_line3
     * @throws InvalidFormatException
     */
    public function setShippingAddressLine3($shipping_address_line3) {
        if (!$this->validateField('shipping_address_line3', $shipping_address_line3)) {
            throw new InvalidFormatException();
        }
        $this->shipping_address_line3 = $shipping_address_line3;
    }

    /**
     * @param string $shipping_address_postal_code
     * @throws InvalidFormatException
     */
    public function setShippingAddressPostalCode($shipping_address_postal_code) {
        if (!$this->validateField('shipping_address_postal_code', $shipping_address_postal_code)) {
            throw new InvalidFormatException();
        }
        $this->shipping_address_postal_code = $shipping_address_postal_code;
    }

    /**
     * @param string $shipping_address_state
     * @throws InvalidFormatException
     */
    public function setShippingAddressState($shipping_address_state) {
        if (!$this->validateField('shipping_address_state', $shipping_address_state)) {
            throw new InvalidFormatException();
        }
        $this->shipping_address_state = $shipping_address_state;
    }

    /**
     * @param string $name
     * @throws InvalidFormatException
     */
    public function setName($name) {
        if (!$this->validateField('name', $name)) {
            throw new InvalidFormatException();
        }
        $this->name = $name;
    }

    /**
     * @param string $email
     * @throws InvalidFormatException
     */
    public function setEmail($email) {
        if (!$this->validateField('email', $email)) {
            throw new InvalidFormatException();
        }
        $this->email = $email;
    }

    /**
     * @param string$countryCode
     * @param string $number
     * @throws InvalidFormatException
     */
    public function setPhoneHome($countryCode, $number) {
        $this->setPhoneHomeCc($countryCode);
        $this->setPhoneHomeNumber($number);
    }

    /**
     * @param string $phone_home_cc
     * @throws InvalidFormatException
     */
    private function setPhoneHomeCc($phone_home_cc) {
        if (!$this->validateField('phone_home_cc', $phone_home_cc)) {
            throw new InvalidFormatException();
        }
        $this->phone_home_cc = $phone_home_cc;
    }

    /**
     * @param string $phone_home_number
     * @throws InvalidFormatException
     */
    private function setPhoneHomeNumber($phone_home_number) {
        if (!$this->validateField('phone_home_number', $phone_home_number)) {
            throw new InvalidFormatException();
        }
        $this->phone_home_number = $phone_home_number;
    }

    /**
     * @param string $countryCode
     * @param string $number
     * @throws InvalidFormatException
     */
    public function setPhoneMobile($countryCode, $number) {
        $this->setPhoneMobileCc($countryCode);
        $this->setPhoneMobileNumber($number);
    }

    /**
     * @param string $phone_mobile_cc
     * @throws InvalidFormatException
     */
    private function setPhoneMobileCc($phone_mobile_cc) {
        if (!$this->validateField('phone_mobile_cc', $phone_mobile_cc)) {
            throw new InvalidFormatException();
        }
        $this->phone_mobile_cc = $phone_mobile_cc;
    }

    /**
     * @param string $phone_mobile_number
     * @throws InvalidFormatException
     */
    private function setPhoneMobileNumber($phone_mobile_number) {
        if (!$this->validateField('phone_mobile_number', $phone_mobile_number)) {
            throw new InvalidFormatException();
        }
        $this->phone_mobile_number = $phone_mobile_number;
    }

    /**
     * @param string $countryCode
     * @param string $number
     * @throws InvalidFormatException
     */
    public function setPhoneWork($countryCode, $number) {
        $this->setPhoneWorkCc($countryCode);
        $this->setPhoneWorkNumber($number);
    }

    /**
     * @param string $phone_work_cc
     * @throws InvalidFormatException
     */
    private function setPhoneWorkCc($phone_work_cc) {
        if (!$this->validateField('phone_work_cc', $phone_work_cc)) {
            throw new InvalidFormatException();
        }
        $this->phone_work_cc = $phone_work_cc;
    }

    /**
     * @param string $phone_work_number
     * @throws InvalidFormatException
     */
    private function setPhoneWorkNumber($phone_work_number) {
        if (!$this->validateField('phone_work_number', $phone_work_number)) {
            throw new InvalidFormatException();
        }
        $this->phone_work_number = $phone_work_number;
    }

    /**
     * @param string $delivery_email
     * @throws InvalidFormatException
     */
    public function setDeliveryEmail($delivery_email) {
        if (!$this->validateField('delivery_email', $delivery_email)) {
            throw new InvalidFormatException();
        }
        $this->delivery_email = $delivery_email;
    }

    /**
     * @param string $delivery_time_frame
     * @throws InvalidFormatException
     */
    public function setDeliveryTimeFrame($delivery_time_frame) {
        if (!$this->validateField('delivery_time_frame', $delivery_time_frame)) {
            throw new InvalidFormatException();
        }
        $this->delivery_time_frame = $delivery_time_frame;
    }

    /**
     * @param string $gift_card_amount
     * @throws InvalidFormatException
     */
    public function setGiftCardAmount($gift_card_amount) {
        if (!$this->validateField('gift_card_amount', $gift_card_amount)) {
            throw new InvalidFormatException();
        }
        $this->gift_card_amount = $gift_card_amount;
    }

    /**
     * @param string $gift_card_count
     * @throws InvalidFormatException
     */
    public function setGiftCardCount($gift_card_count) {
        if (!$this->validateField('gift_card_count', $gift_card_count)) {
            throw new InvalidFormatException();
        }
        $this->gift_card_count = $gift_card_count;
    }

    /**
     * @param string $preorder
     * @throws InvalidFormatException
     */
    public function setPreorder($preorder) {
        if (!$this->validateField('preorder', $preorder)) {
            throw new InvalidFormatException();
        }
        $this->preorder = $preorder;
    }

    /**
     * @param string $preorder_date
     * @throws InvalidFormatException
     */
    public function setPreorderDate($preorder_date) {
        if (!$this->validateField('preorder_date', $preorder_date)) {
            throw new InvalidFormatException();
        }
        $this->preorder_date = $preorder_date;
    }

    /**
     * @param string $reorder
     * @throws InvalidFormatException
     */
    public function setReorder($reorder) {
        if (!$this->validateField('reorder', $reorder)) {
            throw new InvalidFormatException();
        }
        $this->reorder = $reorder;
    }

    /**
     * @param string $shipping_method
     * @throws InvalidFormatException
     */
    public function setShippingMethod($shipping_method) {
        if (!$this->validateField('shipping_method', $shipping_method)) {
            throw new InvalidFormatException();
        }
        $this->shipping_method = $shipping_method;
    }
}
