<?php

namespace Tests\Unit\PaymentWindow;

use OnPay\API\Exception\InvalidFormatException;
use OnPay\API\PaymentWindow\PaymentInfo;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for the PaymentInfo value object.
 *
 * PaymentInfo exposes no getters, so stored values are observed through
 * getFields() (prefixed) and getFieldsWithoutPrefix() (unprefixed).
 */
class PaymentInfoTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     *   [setterMethod, fieldKey, validValue, invalidValue]
     */
    public static function setterProvider(): array
    {
        return [
            'account_id' => ['setAccountId', 'account_id', 'user_123', 'bad id'],
            'account_date_created' => ['setAccountDateCreated', 'account_date_created', '2023-01-15', '2023/01/15'],
            'account_date_change' => ['setAccountDateChange', 'account_date_change', '2023-02-20', '2023/02/20'],
            'account_date_password_change' => ['setAccountDatePasswordChange', 'account_date_password_change', '2023-03-25', '20230325'],
            'account_purchases' => ['setAccountPurchases', 'account_purchases', '5', 'abc'],
            'account_attempts' => ['setAccountAttempts', 'account_attempts', '3', 'abc'],
            'account_shipping_first_use_date' => ['setAccountShippingFirstUseDate', 'account_shipping_first_use_date', '2023-04-01', 'nope'],
            'account_shipping_identical_name' => ['setAccountShippingIdenticalName', 'account_shipping_identical_name', 'Y', 'X'],
            'account_suspicious' => ['setAccountSuspicious', 'account_suspicious', 'N', 'X'],
            'account_attempts_day' => ['setAccountAttemptsDay', 'account_attempts_day', '2', 'abc'],
            'account_attempts_year' => ['setAccountAttemptsYear', 'account_attempts_year', '10', 'abc'],
            'address_identical_shipping' => ['setAddressIdenticalShipping', 'address_identical_shipping', 'Y', 'X'],
            'billing_address_city' => ['setBillingAddressCity', 'billing_address_city', 'Copenhagen', str_repeat('a', 51)],
            'billing_address_country' => ['setBillingAddressCountry', 'billing_address_country', '208', '12'],
            'billing_address_line1' => ['setBillingAddressLine1', 'billing_address_line1', 'Main street 1', str_repeat('a', 51)],
            'billing_address_line2' => ['setBillingAddressLine2', 'billing_address_line2', 'Floor 2', str_repeat('a', 51)],
            'billing_address_line3' => ['setBillingAddressLine3', 'billing_address_line3', 'Door 3', str_repeat('a', 51)],
            'billing_address_postal_code' => ['setBillingAddressPostalCode', 'billing_address_postal_code', '1000', str_repeat('a', 17)],
            'billing_address_state' => ['setBillingAddressState', 'billing_address_state', 'DK', 'ABCD'],
            'shipping_address_city' => ['setShippingAddressCity', 'shipping_address_city', 'Aarhus', str_repeat('a', 51)],
            'shipping_address_country' => ['setShippingAddressCountry', 'shipping_address_country', '208', '12'],
            'shipping_address_line1' => ['setShippingAddressLine1', 'shipping_address_line1', 'Second street 5', str_repeat('a', 51)],
            'shipping_address_line2' => ['setShippingAddressLine2', 'shipping_address_line2', 'Suite 9', str_repeat('a', 51)],
            'shipping_address_line3' => ['setShippingAddressLine3', 'shipping_address_line3', 'Bldg B', str_repeat('a', 51)],
            'shipping_address_postal_code' => ['setShippingAddressPostalCode', 'shipping_address_postal_code', '8000', str_repeat('a', 17)],
            'shipping_address_state' => ['setShippingAddressState', 'shipping_address_state', 'DK', 'ABCD'],
            'name' => ['setName', 'name', 'John Doe', 'a'],
            'email' => ['setEmail', 'email', 'john@example.com', str_repeat('a', 255)],
            'delivery_email' => ['setDeliveryEmail', 'delivery_email', 'delivery@example.com', str_repeat('a', 255)],
            'delivery_time_frame' => ['setDeliveryTimeFrame', 'delivery_time_frame', '01', '1'],
            'gift_card_amount' => ['setGiftCardAmount', 'gift_card_amount', '100', 'abc'],
            'gift_card_count' => ['setGiftCardCount', 'gift_card_count', '2', 'abc'],
            'preorder' => ['setPreorder', 'preorder', 'Y', 'X'],
            'preorder_date' => ['setPreorderDate', 'preorder_date', '2023-12-01', '2023/12/01'],
            'reorder' => ['setReorder', 'reorder', 'N', 'X'],
            'shipping_method' => ['setShippingMethod', 'shipping_method', '01', 'abc'],
        ];
    }

    #[DataProvider('setterProvider')]
    public function testSetterStoresValidValue(string $method, string $key, string $valid): void
    {
        $info = new PaymentInfo();
        $info->{$method}($valid);

        $this->assertSame([$key => $valid], $info->getFieldsWithoutPrefix());
        $this->assertSame(['onpay_info_' . $key => $valid], $info->getFields());
    }

    #[DataProvider('setterProvider')]
    public function testSetterRejectsInvalidValue(string $method, string $key, string $valid, string $invalid): void
    {
        $info = new PaymentInfo();

        $this->expectException(InvalidFormatException::class);
        $info->{$method}($invalid);
    }

    public function testFreshObjectHasNoFields(): void
    {
        $info = new PaymentInfo();

        $this->assertSame([], $info->getFields());
        $this->assertSame([], $info->getFieldsWithoutPrefix());
    }

    public function testNullValueIsAcceptedButOmittedFromOutput(): void
    {
        $info = new PaymentInfo();
        // validateField() returns true for null, so the setter does not throw...
        $info->setAccountId(null);
        // ...but buildFieldArray() skips null properties, so nothing is emitted.
        $this->assertSame([], $info->getFields());
        $this->assertSame([], $info->getFieldsWithoutPrefix());
    }

    public function testFieldsKeepAvailableFieldsDeclarationOrder(): void
    {
        $info = new PaymentInfo();
        // Set in reverse declaration order; output must still follow declaration order.
        $info->setShippingMethod('01');
        $info->setName('John Doe');
        $info->setAccountId('user_123');

        $this->assertSame(
            ['account_id', 'name', 'shipping_method'],
            array_keys($info->getFieldsWithoutPrefix())
        );
    }

    public function testGetFieldsAppliesOnpayInfoPrefix(): void
    {
        $info = new PaymentInfo();
        $info->setEmail('john@example.com');

        $this->assertSame(['onpay_info_email' => 'john@example.com'], $info->getFields());
        $this->assertSame(['email' => 'john@example.com'], $info->getFieldsWithoutPrefix());
    }

    public function testBoundaryLengthsAreAccepted(): void
    {
        $info = new PaymentInfo();
        // account_id: [!-~]{1,64} at max length.
        $info->setAccountId(str_repeat('a', 64));
        // name: .{2,45} at min length.
        $info->setName('ab');
        // billing_address_city: .{1,50} at max length.
        $info->setBillingAddressCity(str_repeat('b', 50));

        $fields = $info->getFieldsWithoutPrefix();
        $this->assertSame(str_repeat('a', 64), $fields['account_id']);
        $this->assertSame('ab', $fields['name']);
        $this->assertSame(str_repeat('b', 50), $fields['billing_address_city']);
    }

    public function testBoundaryLengthsJustOverLimitAreRejected(): void
    {
        $info = new PaymentInfo();

        $this->expectException(InvalidFormatException::class);
        $info->setAccountId(str_repeat('a', 65));
    }

    public function testSetPhoneHomeStoresCountryCodeAndNumber(): void
    {
        $info = new PaymentInfo();
        $info->setPhoneHome('45', '12345678');

        $this->assertSame(
            ['phone_home_cc' => '45', 'phone_home_number' => '12345678'],
            $info->getFieldsWithoutPrefix()
        );
    }

    public function testSetPhoneMobileStoresCountryCodeAndNumber(): void
    {
        $info = new PaymentInfo();
        $info->setPhoneMobile('45', '87654321');

        $this->assertSame(
            ['phone_mobile_cc' => '45', 'phone_mobile_number' => '87654321'],
            $info->getFieldsWithoutPrefix()
        );
    }

    public function testSetPhoneWorkStoresCountryCodeAndNumber(): void
    {
        $info = new PaymentInfo();
        $info->setPhoneWork('45', '11223344');

        $this->assertSame(
            ['phone_work_cc' => '45', 'phone_work_number' => '11223344'],
            $info->getFieldsWithoutPrefix()
        );
    }

    public function testSetPhoneHomeRejectsInvalidCountryCode(): void
    {
        $info = new PaymentInfo();

        $this->expectException(InvalidFormatException::class);
        // cc [0-9]{1,3}: four digits is too long.
        $info->setPhoneHome('1234', '12345678');
    }

    public function testSetPhoneMobileRejectsInvalidCountryCode(): void
    {
        $info = new PaymentInfo();

        $this->expectException(InvalidFormatException::class);
        $info->setPhoneMobile('1234', '12345678');
    }

    public function testSetPhoneMobileRejectsInvalidNumber(): void
    {
        $info = new PaymentInfo();

        $this->expectException(InvalidFormatException::class);
        // number [0-9]{1,15}: sixteen digits is too long.
        $info->setPhoneMobile('45', str_repeat('1', 16));
    }

    public function testSetPhoneWorkRejectsInvalidCountryCode(): void
    {
        $info = new PaymentInfo();

        $this->expectException(InvalidFormatException::class);
        $info->setPhoneWork('1234', '12345678');
    }

    public function testSetPhoneWorkRejectsNonNumericNumber(): void
    {
        $info = new PaymentInfo();

        $this->expectException(InvalidFormatException::class);
        $info->setPhoneWork('45', 'notanumber');
    }

    /**
     * A rejected pair must leave the object untouched. Previously the setter stored the
     * country code first and then threw on the number, leaving `phone_*_cc` set.
     *
     * @param 'setPhoneHome'|'setPhoneMobile'|'setPhoneWork' $setter
     */
    #[DataProvider('phonePairProvider')]
    public function testARejectedPhonePairStoresNeitherValue(string $setter): void
    {
        // A bad number with a good country code, then the reverse.
        foreach ([['45', 'notanumber'], ['1234', '12345678']] as [$countryCode, $number]) {
            $info = new PaymentInfo();
            try {
                $info->{$setter}($countryCode, $number);
                $this->fail(sprintf('%s(%s, %s) was accepted.', $setter, $countryCode, $number));
            } catch (InvalidFormatException $e) {
                $this->assertSame([], $info->getFieldsWithoutPrefix());
            }
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function phonePairProvider(): array
    {
        return [
            'home' => ['setPhoneHome'],
            'mobile' => ['setPhoneMobile'],
            'work' => ['setPhoneWork'],
        ];
    }

    /**
     * The alternation patterns ('Y|N') are interpolated into the anchors, so they must be
     * grouped: without the group, /^Y|N$/u parses as (^Y)|(N$) and accepts any value that
     * merely starts with Y or ends with N.
     */
    #[DataProvider('yesNoFieldProvider')]
    public function testYesNoFieldsAcceptOnlyYAndN(string $setter, string $field): void
    {
        foreach (['Y', 'N'] as $accepted) {
            $info = new PaymentInfo();
            $info->{$setter}($accepted);
            $this->assertSame([$field => $accepted], $info->getFieldsWithoutPrefix());
        }

        // 'Yes' starts with Y and 'ON' ends with N: both were accepted before the
        // alternation was grouped.
        foreach (['Yes', 'ON', 'YN', 'yes', 'n'] as $rejected) {
            $info = new PaymentInfo();
            try {
                $info->{$setter}($rejected);
                $this->fail(sprintf('%s(%s) was accepted.', $setter, var_export($rejected, true)));
            } catch (InvalidFormatException $e) {
                $this->assertSame([], $info->getFieldsWithoutPrefix());
            }
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function yesNoFieldProvider(): array
    {
        return [
            'account_shipping_identical_name' => ['setAccountShippingIdenticalName', 'account_shipping_identical_name'],
            'account_suspicious' => ['setAccountSuspicious', 'account_suspicious'],
            'address_identical_shipping' => ['setAddressIdenticalShipping', 'address_identical_shipping'],
            'preorder' => ['setPreorder', 'preorder'],
            'reorder' => ['setReorder', 'reorder'],
        ];
    }

    public function testTrailingNewlineIsRejected(): void
    {
        // The /D modifier makes $ mean end-of-subject rather than "end of subject, or
        // before a final newline", so "208\n" no longer satisfies [0-9]{3}.
        $info = new PaymentInfo();

        $this->expectException(InvalidFormatException::class);
        $info->setBillingAddressCountry("208\n");
    }

    public function testTrailingNewlineIsRejectedOnAnOpenEndedPattern(): void
    {
        // Also covers a pattern that is not fixed-length: account_purchases is [0-9]+.
        $info = new PaymentInfo();

        $this->expectException(InvalidFormatException::class);
        $info->setAccountPurchases("12\n");
    }

    public function testDeliveryTimeframeConstants(): void
    {
        $this->assertSame('01', PaymentInfo::DELIVERY_TIMEFRAME_ELECTRONIC);
        $this->assertSame('02', PaymentInfo::DELIVERY_TIMEFRAME_SAMEDAY);
        $this->assertSame('03', PaymentInfo::DELIVERY_TIMEFRAME_OVERNIGHT);
        $this->assertSame('04', PaymentInfo::DELIVERY_TIMEFRAME_TWODAY);
    }

    public function testShippingMethodConstants(): void
    {
        $this->assertSame('01', PaymentInfo::SHIPPING_METHOD_BILLING);
        $this->assertSame('02', PaymentInfo::SHIPPING_METHOD_VERIFIED_ADDRESS);
        $this->assertSame('03', PaymentInfo::SHIPPING_METHOD_OTHER_ADDRESS);
        $this->assertSame('04', PaymentInfo::SHIPPING_METHOD_STORE);
        $this->assertSame('05', PaymentInfo::SHIPPING_METHOD_DIGITAL);
        $this->assertSame('06', PaymentInfo::SHIPPING_METHOD_TRAVEL_EVENT);
        $this->assertSame('07', PaymentInfo::SHIPPING_METHOD_OTHER);
    }
}
