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

    /**
     * @var array<string, string>
     */
    protected $availableFields;
    protected ?string $account_id = null;
    protected ?string $account_date_created = null;
    protected ?string $account_date_change = null;
    protected ?string $account_date_password_change = null;
    protected ?string $account_purchases = null;
    protected ?string $account_attempts = null;
    protected ?string $account_shipping_first_use_date = null;
    protected ?string $account_shipping_identical_name = null;
    protected ?string $account_suspicious = null;
    protected ?string $account_attempts_day = null;
    protected ?string $account_attempts_year = null;
    protected ?string $address_identical_shipping = null;
    protected ?string $billing_address_city = null;
    protected ?string $billing_address_country = null;
    protected ?string $billing_address_line1 = null;
    protected ?string $billing_address_line2 = null;
    protected ?string $billing_address_line3 = null;
    protected ?string $billing_address_postal_code = null;
    protected ?string $billing_address_state = null;
    protected ?string $shipping_address_city = null;
    protected ?string $shipping_address_country = null;
    protected ?string $shipping_address_line1 = null;
    protected ?string $shipping_address_line2 = null;
    protected ?string $shipping_address_line3 = null;
    protected ?string $shipping_address_postal_code = null;
    protected ?string $shipping_address_state = null;
    protected ?string $name = null;
    protected ?string $email = null;
    protected ?string $phone_home_cc = null;
    protected ?string $phone_home_number = null;
    protected ?string $phone_mobile_cc = null;
    protected ?string $phone_mobile_number = null;
    protected ?string $phone_work_cc = null;
    protected ?string $phone_work_number = null;
    protected ?string $delivery_email = null;
    protected ?string $delivery_time_frame = null;
    protected ?string $gift_card_amount = null;
    protected ?string $gift_card_count = null;
    protected ?string $preorder = null;
    protected ?string $preorder_date = null;
    protected ?string $reorder = null;
    protected ?string $shipping_method = null;

    public function __construct() {
        $this->availableFields = [
            'account_id' => '[!-~]{1,64}',
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
            'billing_address_postal_code' => '[\w\s-]{1,16}',
            'billing_address_state' => '\w{1,3}',
            'shipping_address_city' => '.{1,50}',
            'shipping_address_country' => '[0-9]{3}',
            'shipping_address_line1' => '.{1,50}',
            'shipping_address_line2' => '.{1,50}',
            'shipping_address_line3' => '.{1,50}',
            'shipping_address_postal_code' => '[\w\s-]{1,16}',
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

    /**
     * @param string $name
     * @param string|null $value
     */
    protected function validateField($name, $value): bool {
        if (isset($this->availableFields[$name])) {
            if (null === $value) {
                return true;
            }
            if (1 === preg_match('/^' . $this->availableFields[$name] . '$/u', $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @internal Meant only for internal use
     */
    public function getFields(): array {
        return $this->buildFieldArray();
    }

    /**
     * @internal Meant only for internal use
     */
    public function getFieldsWithoutPrefix(): array {
        return $this->buildFieldArray(false);
    }

    /**
     * @param bool $withPrefix
     * @return array
     */
    private function buildFieldArray($withPrefix = true): array {
        $prefix = $withPrefix ? 'onpay_info_' : '';
        $values = [
            'account_id' => $this->account_id,
            'account_date_created' => $this->account_date_created,
            'account_date_change' => $this->account_date_change,
            'account_date_password_change' => $this->account_date_password_change,
            'account_purchases' => $this->account_purchases,
            'account_attempts' => $this->account_attempts,
            'account_shipping_first_use_date' => $this->account_shipping_first_use_date,
            'account_shipping_identical_name' => $this->account_shipping_identical_name,
            'account_suspicious' => $this->account_suspicious,
            'account_attempts_day' => $this->account_attempts_day,
            'account_attempts_year' => $this->account_attempts_year,
            'address_identical_shipping' => $this->address_identical_shipping,
            'billing_address_city' => $this->billing_address_city,
            'billing_address_country' => $this->billing_address_country,
            'billing_address_line1' => $this->billing_address_line1,
            'billing_address_line2' => $this->billing_address_line2,
            'billing_address_line3' => $this->billing_address_line3,
            'billing_address_postal_code' => $this->billing_address_postal_code,
            'billing_address_state' => $this->billing_address_state,
            'shipping_address_city' => $this->shipping_address_city,
            'shipping_address_country' => $this->shipping_address_country,
            'shipping_address_line1' => $this->shipping_address_line1,
            'shipping_address_line2' => $this->shipping_address_line2,
            'shipping_address_line3' => $this->shipping_address_line3,
            'shipping_address_postal_code' => $this->shipping_address_postal_code,
            'shipping_address_state' => $this->shipping_address_state,
            'name' => $this->name,
            'email' => $this->email,
            'phone_home_cc' => $this->phone_home_cc,
            'phone_home_number' => $this->phone_home_number,
            'phone_mobile_cc' => $this->phone_mobile_cc,
            'phone_mobile_number' => $this->phone_mobile_number,
            'phone_work_cc' => $this->phone_work_cc,
            'phone_work_number' => $this->phone_work_number,
            'delivery_email' => $this->delivery_email,
            'delivery_time_frame' => $this->delivery_time_frame,
            'gift_card_amount' => $this->gift_card_amount,
            'gift_card_count' => $this->gift_card_count,
            'preorder' => $this->preorder,
            'preorder_date' => $this->preorder_date,
            'reorder' => $this->reorder,
            'shipping_method' => $this->shipping_method,
        ];

        $fields = [];
        foreach ($values as $field => $value) {
            if (null !== $value) {
                $fields[$prefix . $field] = $value;
            }
        }

        return $fields;
    }

    /**
     * @param string $account_id
     * @throws InvalidFormatException
     */
    public function setAccountId($account_id): void {
        if (!$this->validateField('account_id', $account_id)) {
            throw new InvalidFormatException();
        }
        $this->account_id = $account_id;
    }

    /**
     * @param string $account_date_created
     * @throws InvalidFormatException
     */
    public function setAccountDateCreated($account_date_created): void {
        if (!$this->validateField('account_date_created', $account_date_created)) {
            throw new InvalidFormatException();
        }
        $this->account_date_created = $account_date_created;
    }

    /**
     * @param string $account_date_change
     * @throws InvalidFormatException
     */
    public function setAccountDateChange($account_date_change): void {
        if (!$this->validateField('account_date_change', $account_date_change)) {
            throw new InvalidFormatException();
        }
        $this->account_date_change = $account_date_change;
    }

    /**
     * @param string $account_date_password_change
     * @throws InvalidFormatException
     */
    public function setAccountDatePasswordChange($account_date_password_change): void {
        if (!$this->validateField('account_date_password_change', $account_date_password_change)) {
            throw new InvalidFormatException();
        }
        $this->account_date_password_change = $account_date_password_change;
    }

    /**
     * @param string $account_purchases
     * @throws InvalidFormatException
     */
    public function setAccountPurchases($account_purchases): void {
        if (!$this->validateField('account_purchases', $account_purchases)) {
            throw new InvalidFormatException();
        }
        $this->account_purchases = $account_purchases;
    }

    /**
     * @param string $account_attempts
     * @throws InvalidFormatException
     */
    public function setAccountAttempts($account_attempts): void {
        if (!$this->validateField('account_attempts', $account_attempts)) {
            throw new InvalidFormatException();
        }
        $this->account_attempts = $account_attempts;
    }

    /**
     * @param string $account_shipping_first_use_date
     * @throws InvalidFormatException
     */
    public function setAccountShippingFirstUseDate($account_shipping_first_use_date): void {
        if (!$this->validateField('account_shipping_first_use_date', $account_shipping_first_use_date)) {
            throw new InvalidFormatException();
        }
        $this->account_shipping_first_use_date = $account_shipping_first_use_date;
    }

    /**
     * @param string $account_shipping_identical_name
     * @throws InvalidFormatException
     */
    public function setAccountShippingIdenticalName($account_shipping_identical_name): void {
        if (!$this->validateField('account_shipping_identical_name', $account_shipping_identical_name)) {
            throw new InvalidFormatException();
        }
        $this->account_shipping_identical_name = $account_shipping_identical_name;
    }

    /**
     * @param string $account_suspicious
     * @throws InvalidFormatException
     */
    public function setAccountSuspicious($account_suspicious): void {
        if (!$this->validateField('account_suspicious', $account_suspicious)) {
            throw new InvalidFormatException();
        }
        $this->account_suspicious = $account_suspicious;
    }

    /**
     * @param string $account_attempts_day
     * @throws InvalidFormatException
     */
    public function setAccountAttemptsDay($account_attempts_day): void {
        if (!$this->validateField('account_attempts_day', $account_attempts_day)) {
            throw new InvalidFormatException();
        }
        $this->account_attempts_day = $account_attempts_day;
    }

    /**
     * @param string $account_attempts_year
     * @throws InvalidFormatException
     */
    public function setAccountAttemptsYear($account_attempts_year): void {
        if (!$this->validateField('account_attempts_year', $account_attempts_year)) {
            throw new InvalidFormatException();
        }
        $this->account_attempts_year = $account_attempts_year;
    }

    /**
     * @param string $address_identical_shipping
     * @throws InvalidFormatException
     */
    public function setAddressIdenticalShipping($address_identical_shipping): void {
        if (!$this->validateField('address_identical_shipping', $address_identical_shipping)) {
            throw new InvalidFormatException();
        }
        $this->address_identical_shipping = $address_identical_shipping;
    }

    /**
     * @param string $billing_address_city
     * @throws InvalidFormatException
     */
    public function setBillingAddressCity($billing_address_city): void {
        if (!$this->validateField('billing_address_city', $billing_address_city)) {
            throw new InvalidFormatException();
        }
        $this->billing_address_city = $billing_address_city;
    }

    /**
     * @param string $billing_address_country
     * @throws InvalidFormatException
     */
    public function setBillingAddressCountry($billing_address_country): void {
        if (!$this->validateField('billing_address_country', $billing_address_country)) {
            throw new InvalidFormatException();
        }
        $this->billing_address_country = $billing_address_country;
    }

    /**
     * @param string $billing_address_line1
     * @throws InvalidFormatException
     */
    public function setBillingAddressLine1($billing_address_line1): void {
        if (!$this->validateField('billing_address_line1', $billing_address_line1)) {
            throw new InvalidFormatException();
        }
        $this->billing_address_line1 = $billing_address_line1;
    }

    /**
     * @param string $billing_address_line2
     * @throws InvalidFormatException
     */
    public function setBillingAddressLine2($billing_address_line2): void {
        if (!$this->validateField('billing_address_line2', $billing_address_line2)) {
            throw new InvalidFormatException();
        }
        $this->billing_address_line2 = $billing_address_line2;
    }

    /**
     * @param string $billing_address_line3
     * @throws InvalidFormatException
     */
    public function setBillingAddressLine3($billing_address_line3): void {
        if (!$this->validateField('billing_address_line3', $billing_address_line3)) {
            throw new InvalidFormatException();
        }
        $this->billing_address_line3 = $billing_address_line3;
    }

    /**
     * @param string $billing_address_postal_code
     * @throws InvalidFormatException
     */
    public function setBillingAddressPostalCode($billing_address_postal_code): void {
        if (!$this->validateField('billing_address_postal_code', $billing_address_postal_code)) {
            throw new InvalidFormatException();
        }
        $this->billing_address_postal_code = $billing_address_postal_code;
    }

    /**
     * @param string $billing_address_state
     * @throws InvalidFormatException
     */
    public function setBillingAddressState($billing_address_state): void {
        if (!$this->validateField('billing_address_state', $billing_address_state)) {
            throw new InvalidFormatException();
        }
        $this->billing_address_state = $billing_address_state;
    }

    /**
     * @param string $shipping_address_city
     * @throws InvalidFormatException
     */
    public function setShippingAddressCity($shipping_address_city): void {
        if (!$this->validateField('shipping_address_city', $shipping_address_city)) {
            throw new InvalidFormatException();
        }
        $this->shipping_address_city = $shipping_address_city;
    }

    /**
     * @param string $shipping_address_country
     * @throws InvalidFormatException
     */
    public function setShippingAddressCountry($shipping_address_country): void {
        if (!$this->validateField('shipping_address_country', $shipping_address_country)) {
            throw new InvalidFormatException();
        }
        $this->shipping_address_country = $shipping_address_country;
    }

    /**
     * @param string $shipping_address_line1
     * @throws InvalidFormatException
     */
    public function setShippingAddressLine1($shipping_address_line1): void {
        if (!$this->validateField('shipping_address_line1', $shipping_address_line1)) {
            throw new InvalidFormatException();
        }
        $this->shipping_address_line1 = $shipping_address_line1;
    }

    /**
     * @param string $shipping_address_line2
     * @throws InvalidFormatException
     */
    public function setShippingAddressLine2($shipping_address_line2): void {
        if (!$this->validateField('shipping_address_line2', $shipping_address_line2)) {
            throw new InvalidFormatException();
        }
        $this->shipping_address_line2 = $shipping_address_line2;
    }

    /**
     * @param string $shipping_address_line3
     * @throws InvalidFormatException
     */
    public function setShippingAddressLine3($shipping_address_line3): void {
        if (!$this->validateField('shipping_address_line3', $shipping_address_line3)) {
            throw new InvalidFormatException();
        }
        $this->shipping_address_line3 = $shipping_address_line3;
    }

    /**
     * @param string $shipping_address_postal_code
     * @throws InvalidFormatException
     */
    public function setShippingAddressPostalCode($shipping_address_postal_code): void {
        if (!$this->validateField('shipping_address_postal_code', $shipping_address_postal_code)) {
            throw new InvalidFormatException();
        }
        $this->shipping_address_postal_code = $shipping_address_postal_code;
    }

    /**
     * @param string $shipping_address_state
     * @throws InvalidFormatException
     */
    public function setShippingAddressState($shipping_address_state): void {
        if (!$this->validateField('shipping_address_state', $shipping_address_state)) {
            throw new InvalidFormatException();
        }
        $this->shipping_address_state = $shipping_address_state;
    }

    /**
     * @param string $name
     * @throws InvalidFormatException
     */
    public function setName($name): void {
        if (!$this->validateField('name', $name)) {
            throw new InvalidFormatException();
        }
        $this->name = $name;
    }

    /**
     * @param string $email
     * @throws InvalidFormatException
     */
    public function setEmail($email): void {
        if (!$this->validateField('email', $email)) {
            throw new InvalidFormatException();
        }
        $this->email = $email;
    }

    /**
     * @param string $countryCode
     * @param string $number
     * @throws InvalidFormatException
     */
    public function setPhoneHome($countryCode, $number): void {
        $this->setPhoneHomeCc($countryCode);
        $this->setPhoneHomeNumber($number);
    }

    /**
     * @param string $phone_home_cc
     * @throws InvalidFormatException
     */
    private function setPhoneHomeCc($phone_home_cc): void {
        if (!$this->validateField('phone_home_cc', $phone_home_cc)) {
            throw new InvalidFormatException();
        }
        $this->phone_home_cc = $phone_home_cc;
    }

    /**
     * @param string $phone_home_number
     * @throws InvalidFormatException
     */
    private function setPhoneHomeNumber($phone_home_number): void {
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
    public function setPhoneMobile($countryCode, $number): void {
        $this->setPhoneMobileCc($countryCode);
        $this->setPhoneMobileNumber($number);
    }

    /**
     * @param string $phone_mobile_cc
     * @throws InvalidFormatException
     */
    private function setPhoneMobileCc($phone_mobile_cc): void {
        if (!$this->validateField('phone_mobile_cc', $phone_mobile_cc)) {
            throw new InvalidFormatException();
        }
        $this->phone_mobile_cc = $phone_mobile_cc;
    }

    /**
     * @param string $phone_mobile_number
     * @throws InvalidFormatException
     */
    private function setPhoneMobileNumber($phone_mobile_number): void {
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
    public function setPhoneWork($countryCode, $number): void {
        $this->setPhoneWorkCc($countryCode);
        $this->setPhoneWorkNumber($number);
    }

    /**
     * @param string $phone_work_cc
     * @throws InvalidFormatException
     */
    private function setPhoneWorkCc($phone_work_cc): void {
        if (!$this->validateField('phone_work_cc', $phone_work_cc)) {
            throw new InvalidFormatException();
        }
        $this->phone_work_cc = $phone_work_cc;
    }

    /**
     * @param string $phone_work_number
     * @throws InvalidFormatException
     */
    private function setPhoneWorkNumber($phone_work_number): void {
        if (!$this->validateField('phone_work_number', $phone_work_number)) {
            throw new InvalidFormatException();
        }
        $this->phone_work_number = $phone_work_number;
    }

    /**
     * @param string $delivery_email
     * @throws InvalidFormatException
     */
    public function setDeliveryEmail($delivery_email): void {
        if (!$this->validateField('delivery_email', $delivery_email)) {
            throw new InvalidFormatException();
        }
        $this->delivery_email = $delivery_email;
    }

    /**
     * @param string $delivery_time_frame
     * @throws InvalidFormatException
     */
    public function setDeliveryTimeFrame($delivery_time_frame): void {
        if (!$this->validateField('delivery_time_frame', $delivery_time_frame)) {
            throw new InvalidFormatException();
        }
        $this->delivery_time_frame = $delivery_time_frame;
    }

    /**
     * @param string $gift_card_amount
     * @throws InvalidFormatException
     */
    public function setGiftCardAmount($gift_card_amount): void {
        if (!$this->validateField('gift_card_amount', $gift_card_amount)) {
            throw new InvalidFormatException();
        }
        $this->gift_card_amount = $gift_card_amount;
    }

    /**
     * @param string $gift_card_count
     * @throws InvalidFormatException
     */
    public function setGiftCardCount($gift_card_count): void {
        if (!$this->validateField('gift_card_count', $gift_card_count)) {
            throw new InvalidFormatException();
        }
        $this->gift_card_count = $gift_card_count;
    }

    /**
     * @param string $preorder
     * @throws InvalidFormatException
     */
    public function setPreorder($preorder): void {
        if (!$this->validateField('preorder', $preorder)) {
            throw new InvalidFormatException();
        }
        $this->preorder = $preorder;
    }

    /**
     * @param string $preorder_date
     * @throws InvalidFormatException
     */
    public function setPreorderDate($preorder_date): void {
        if (!$this->validateField('preorder_date', $preorder_date)) {
            throw new InvalidFormatException();
        }
        $this->preorder_date = $preorder_date;
    }

    /**
     * @param string $reorder
     * @throws InvalidFormatException
     */
    public function setReorder($reorder): void {
        if (!$this->validateField('reorder', $reorder)) {
            throw new InvalidFormatException();
        }
        $this->reorder = $reorder;
    }

    /**
     * @param string $shipping_method
     * @throws InvalidFormatException
     */
    public function setShippingMethod($shipping_method): void {
        if (!$this->validateField('shipping_method', $shipping_method)) {
            throw new InvalidFormatException();
        }
        $this->shipping_method = $shipping_method;
    }
}
