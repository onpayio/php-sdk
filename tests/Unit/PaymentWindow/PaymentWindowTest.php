<?php

namespace Tests\Unit\PaymentWindow;

use OnPay\API\Enum\DeliveryDisabled;
use OnPay\API\Enum\PaymentMethod;
use OnPay\API\Exception\InvalidCartException;
use OnPay\API\PaymentWindow;
use OnPay\API\PaymentWindow\Cart;
use OnPay\API\PaymentWindow\CartItem;
use OnPay\API\PaymentWindow\PaymentInfo;
use OnPay\OnPayAPI;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for the PaymentWindow form builder / HMAC signer.
 *
 * The class is a standalone value object, so every test constructs it directly.
 * Where the class has a known bug (loose onpay_ matching in validatePayment, and
 * comparing the HMAC with === instead of hash_equals) the tests assert the
 * CURRENT behaviour and are marked with a "6329 changes this" comment.
 */
#[CoversClass(PaymentWindow::class)]
class PaymentWindowTest extends TestCase
{
    /**
     * Builds a fully populated, valid window with a known secret so the HMAC
     * output is deterministic and can be pinned as a literal.
     */
    private function makeWindow(): PaymentWindow
    {
        $window = new PaymentWindow();
        $window->setGatewayId('1234567');
        $window->setCurrency('DKK');
        $window->setAmount('12300');
        $window->setReference('order-42');
        $window->setAcceptUrl('https://example.com/accept');
        $window->setSecret('hmacsecret');

        return $window;
    }

    // ---------------------------------------------------------------------
    // Constants / defaults
    // ---------------------------------------------------------------------

    public function testMethodConstants(): void
    {
        $this->assertSame('card', PaymentWindow::METHOD_CARD);
        $this->assertSame('mobilepay', PaymentWindow::METHOD_MOBILEPAY);
        $this->assertSame('mobilepay_checkout', PaymentWindow::METHOD_MOBILEPAY_CHECKOUT);
        $this->assertSame('viabill', PaymentWindow::METHOD_VIABILL);
        $this->assertSame('anyday', PaymentWindow::METHOD_ANYDAY);
        $this->assertSame('applepay', PaymentWindow::METHOD_APPLEPAY);
        $this->assertSame('googlepay', PaymentWindow::METHOD_GOOGLEPAY);
        $this->assertSame('vipps', PaymentWindow::METHOD_VIPPS);
        $this->assertSame('swish', PaymentWindow::METHOD_SWISH);
        $this->assertSame('paypal', PaymentWindow::METHOD_PAYPAL);
        $this->assertSame('klarna', PaymentWindow::METHOD_KLARNA);
    }

    public function testDeliveryDisabledConstants(): void
    {
        $this->assertSame('no-reason', PaymentWindow::DELIVERY_DISABLED_NO_REASON);
        $this->assertSame('not-physical', PaymentWindow::DELIVERY_DISABLED_NOT_PHYSICAL);
        $this->assertSame('store-pick-up', PaymentWindow::DELIVERY_DISABLED_STORE_PICK_UP);
        $this->assertSame('parcel-shop-selected', PaymentWindow::DELIVERY_DISABLED_PARCEL_SHOP_SELECTED);
        $this->assertSame('parcel-shop-auto', PaymentWindow::DELIVERY_DISABLED_PARCEL_SHOP_AUTO);
    }

    public function testSdkVersionConstants(): void
    {
        $this->assertSame(OnPayAPI::SDK_VERSION, PaymentWindow::SDK_VERSION);
        $this->assertSame('php-sdk/' . OnPayAPI::SDK_VERSION, PaymentWindow::SDK_VERSION_STRING);
    }

    public function testConstructorDefaultsPlatformToSdkVersionString(): void
    {
        $window = new PaymentWindow();
        $this->assertSame(PaymentWindow::SDK_VERSION_STRING, $window->getPlatform());
    }

    public function testDefaultLanguageIsNull(): void
    {
        // NOTE: the class sets NO default language; getLanguage() returns null
        // until setLanguage() is called. Asserts current behaviour.
        $window = new PaymentWindow();
        $this->assertNull($window->getLanguage());
    }

    public function testActionUrlDefault(): void
    {
        $window = new PaymentWindow();
        $this->assertSame('https://onpay.io/window/v3/', $window->getActionUrl());
    }

    // ---------------------------------------------------------------------
    // Simple setters / getters
    // ---------------------------------------------------------------------

    public function testScalarSettersAndGetters(): void
    {
        $window = new PaymentWindow();

        $window->setGatewayId('gw-1');
        $this->assertSame('gw-1', $window->getGatewayId());

        $window->setCurrency('DKK');
        $this->assertSame('DKK', $window->getCurrency());

        $window->setAmount('999');
        $this->assertSame('999', $window->getAmount());

        $window->setReference('ref-9');
        $this->assertSame('ref-9', $window->getReference());

        $window->setAcceptUrl('https://accept');
        $this->assertSame('https://accept', $window->getAcceptUrl());

        $window->setType('subscription');
        $this->assertSame('subscription', $window->getType());

        $window->setMethod(PaymentWindow::METHOD_CARD);
        $this->assertSame('card', $window->getMethod());

        $window->setLanguage('da');
        $this->assertSame('da', $window->getLanguage());

        $window->setDeclineUrl('https://decline');
        $this->assertSame('https://decline', $window->getDeclineUrl());

        $window->setCallbackUrl('https://callback');
        $this->assertSame('https://callback', $window->getCallbackUrl());

        $window->setDesign('mydesign');
        $this->assertSame('mydesign', $window->getDesign());

        $window->setTestMode(1);
        $this->assertSame(1, $window->getTestMode());

        $window->setSecret('abc');
        $this->assertSame('abc', $window->getSecret());

        $window->setDeliveryDisabled(PaymentWindow::DELIVERY_DISABLED_NO_REASON);
        $this->assertSame('no-reason', $window->getDeliveryDisabled());

        $window->setWebsite('https://shop.example');
        $this->assertSame('https://shop.example', $window->getWebsite());

        $window->setExpiration(300);
        $this->assertSame(300, $window->getExpiration());

        $window->setSurchargeVatRate(25);
        $this->assertSame(25, $window->getSurchargeVatRate());
    }

    public function testSetMethodAcceptsAStringOrAPaymentMethodEnum(): void
    {
        $window = new PaymentWindow();

        // The enum is unwrapped to its wire value; getMethod() keeps returning the raw string.
        $window->setMethod(PaymentMethod::MOBILEPAY);
        $this->assertSame('mobilepay', $window->getMethod());

        $window->setMethod('mobilepay');
        $this->assertSame('mobilepay', $window->getMethod());
    }

    public function testStringAndEnumMethodProduceIdenticalFormFields(): void
    {
        $fromString = $this->makeWindow();
        $fromString->setMethod('card');

        $fromEnum = $this->makeWindow();
        $fromEnum->setMethod(PaymentMethod::CARD);

        // Identical down to the HMAC, so switching to the enum cannot change the signature.
        $this->assertSame($fromString->getFormFields(), $fromEnum->getFormFields());
        $this->assertSame('card', $fromEnum->getFormFields()['onpay_method']);
    }

    public function testSetMethodAcceptsAMethodTheSdkDoesNotKnow(): void
    {
        // The gateway can add a method before the SDK does, so arbitrary strings must pass
        // through unvalidated and reach the window as-is.
        $window = $this->makeWindow();
        $window->setMethod('some-future-method');

        $this->assertSame('some-future-method', $window->getMethod());
        $this->assertSame('some-future-method', $window->getFormFields()['onpay_method']);
    }

    public function testSetDeliveryDisabledAcceptsAStringAnEnumOrNull(): void
    {
        $window = new PaymentWindow();

        $window->setDeliveryDisabled(DeliveryDisabled::NOT_PHYSICAL);
        $this->assertSame('not-physical', $window->getDeliveryDisabled());

        $window->setDeliveryDisabled('not-physical');
        $this->assertSame('not-physical', $window->getDeliveryDisabled());

        $window->setDeliveryDisabled(null);
        $this->assertNull($window->getDeliveryDisabled());
    }

    public function testStringAndEnumDeliveryDisabledProduceIdenticalFormFields(): void
    {
        $fromString = $this->makeWindow();
        $fromString->setDeliveryDisabled('store-pick-up');

        $fromEnum = $this->makeWindow();
        $fromEnum->setDeliveryDisabled(DeliveryDisabled::STORE_PICK_UP);

        $this->assertSame($fromString->getFormFields(), $fromEnum->getFormFields());
        $this->assertSame('store-pick-up', $fromEnum->getFormFields()['onpay_delivery_disabled']);
    }

    public function testSurchargeEnabledFlag(): void
    {
        $window = new PaymentWindow();
        $this->assertNull($window->isSurcharge_enabled());

        $window->setSurchargeEnabled(true);
        $this->assertTrue($window->isSurcharge_enabled());
    }

    public function testSurchargeEnabledFlagViaTheCorrectlyNamedGetter(): void
    {
        $window = new PaymentWindow();
        $this->assertNull($window->isSurchargeEnabled());

        $window->setSurchargeEnabled(true);
        $this->assertTrue($window->isSurchargeEnabled());

        $window->setSurchargeEnabled(false);
        $this->assertFalse($window->isSurchargeEnabled());
    }

    public function testDeprecatedSurchargeEnabledGetterReturnsTheSameValue(): void
    {
        $window = new PaymentWindow();
        $this->assertSame($window->isSurchargeEnabled(), $window->isSurcharge_enabled());

        $window->setSurchargeEnabled(true);
        $this->assertSame($window->isSurchargeEnabled(), $window->isSurcharge_enabled());

        $window->setSurchargeEnabled(false);
        $this->assertSame($window->isSurchargeEnabled(), $window->isSurcharge_enabled());
    }

    // ---------------------------------------------------------------------
    // 3D-Secure (incl. deprecated aliases)
    // ---------------------------------------------------------------------

    public function test3DSecureTrueSetsForced(): void
    {
        $window = new PaymentWindow();
        $window->set3DSecure(true);
        $this->assertTrue($window->is3DSecure());
    }

    public function test3DSecureFalseClears(): void
    {
        $window = new PaymentWindow();
        $window->set3DSecure(true);
        $window->set3DSecure(false);
        $this->assertFalse($window->is3DSecure());
    }

    public function testDeprecatedSecureEnabledAliasesDelegate(): void
    {
        $window = new PaymentWindow();
        $window->setSecureEnabled(true);
        $this->assertTrue($window->hasSecureEnabled());
        $this->assertTrue($window->is3DSecure());

        $window->setSecureEnabled(false);
        $this->assertFalse($window->hasSecureEnabled());
    }

    // ---------------------------------------------------------------------
    // Subscription-with-transaction flag
    // ---------------------------------------------------------------------

    public function testSubscriptionWithTransactionTrue(): void
    {
        $window = new PaymentWindow();
        $window->setSubscriptionWithTransaction(true);
        $this->assertTrue($window->isSubscriptionWithTransaction());
    }

    public function testSubscriptionWithTransactionFalseClears(): void
    {
        $window = new PaymentWindow();
        $window->setSubscriptionWithTransaction(true);
        $window->setSubscriptionWithTransaction(false);
        $this->assertFalse($window->isSubscriptionWithTransaction());
    }

    // ---------------------------------------------------------------------
    // setPlatform() concatenation branches
    // ---------------------------------------------------------------------

    /**
     * @return array<string, array{0: string, 1: string|null, 2: string|null, 3: string}>
     */
    public static function platformProvider(): array
    {
        return [
            'name only'                 => ['php-sdk', null, null, 'php-sdk'],
            'name and version'          => ['php-sdk', '1', null, 'php-sdk/1'],
            'name and system version'   => ['php-sdk', null, '1', 'php-sdk//1'],
            'all three'                 => ['php-sdk', '1', '2', 'php-sdk/1/2'],
        ];
    }

    #[DataProvider('platformProvider')]
    public function testSetPlatform(string $platform, ?string $version, ?string $systemVersion, string $expected): void
    {
        $window = new PaymentWindow();
        $window->setPlatform($platform, $version, $systemVersion);
        $this->assertSame($expected, $window->getPlatform());
    }

    // ---------------------------------------------------------------------
    // Info / Cart accessors
    // ---------------------------------------------------------------------

    public function testInfoAccessor(): void
    {
        $window = new PaymentWindow();
        $this->assertNull($window->getInfo());

        $info = new PaymentInfo();
        $window->setInfo($info);
        $this->assertSame($info, $window->getInfo());
    }

    public function testCartAccessorAndNullDefaultArgument(): void
    {
        $window = new PaymentWindow();
        $this->assertNull($window->getCart());

        $cart = new Cart();
        $window->setCart($cart);
        $this->assertSame($cart, $window->getCart());

        // Exercise the ?Cart $cart = null default argument.
        $window->setCart();
        $this->assertNull($window->getCart());
    }

    // ---------------------------------------------------------------------
    // Field building + HMAC generation
    // ---------------------------------------------------------------------

    public function testGetFormFieldsBuildsPrefixedFieldsAndPinnedHmac(): void
    {
        $window = $this->makeWindow();

        // Pinned literal: a regression in the signing logic must change this.
        $expected = [
            'onpay_accepturl' => 'https://example.com/accept',
            'onpay_amount'    => '12300',
            'onpay_currency'  => 'DKK',
            'onpay_gatewayid' => '1234567',
            'onpay_platform'  => 'php-sdk/' . OnPayAPI::SDK_VERSION,
            'onpay_reference' => 'order-42',
            // hmac is appended AFTER ksort(), so it stays last, not alphabetical.
            'onpay_hmac_sha1' => '927a0a061a4795f0adb7c236dbe1bdb3e53a4dad',
        ];

        $this->assertSame($expected, $window->getFormFields());
    }

    public function testGenerateSecretMatchesPinnedHmac(): void
    {
        $window = $this->makeWindow();
        $this->assertSame('927a0a061a4795f0adb7c236dbe1bdb3e53a4dad', $window->generateSecret());
    }

    public function testGetAvailableFieldsHasNoPrefix(): void
    {
        $window = $this->makeWindow();

        $expected = [
            'accepturl' => 'https://example.com/accept',
            'amount'    => '12300',
            'currency'  => 'DKK',
            'gatewayid' => '1234567',
            'platform'  => 'php-sdk/' . OnPayAPI::SDK_VERSION,
            'reference' => 'order-42',
        ];

        $this->assertSame($expected, $window->getAvailableFields());
    }

    public function testUnderscoreFieldGetsStrippedLeadingUnderscore(): void
    {
        // Exercises the "0 === strpos($field, '_')" branch: _3dsecure -> 3dsecure.
        $window = $this->makeWindow();
        $window->set3DSecure(true);

        $fields = $window->getFormFields();
        $this->assertArrayHasKey('onpay_3dsecure', $fields);
        $this->assertSame('forced', $fields['onpay_3dsecure']);
    }

    public function testSurchargeEnabledFalseIsStillEmitted(): void
    {
        // Only null omits a field; false is not null, so it is emitted (as "0").
        $window = $this->makeWindow();
        $window->setSurchargeEnabled(false);

        $fields = $window->getFormFields();
        $this->assertArrayHasKey('onpay_surcharge_enabled', $fields);
        $this->assertFalse($fields['onpay_surcharge_enabled']);
    }

    public function testFormFieldsIncludePaymentInfoWithPrefix(): void
    {
        $window = $this->makeWindow();

        $info = new PaymentInfo();
        $info->setName('John Doe');
        $window->setInfo($info);

        $fields = $window->getFormFields();
        $this->assertArrayHasKey('onpay_info_name', $fields);
        $this->assertSame('John Doe', $fields['onpay_info_name']);
    }

    public function testAvailableFieldsIncludePaymentInfoWithoutPrefix(): void
    {
        $window = $this->makeWindow();

        $info = new PaymentInfo();
        $info->setName('John Doe');
        $window->setInfo($info);

        $fields = $window->getAvailableFields();
        $this->assertArrayHasKey('name', $fields);
        $this->assertSame('John Doe', $fields['name']);
        $this->assertArrayNotHasKey('onpay_info_name', $fields);
    }

    public function testFormFieldsIncludeValidCart(): void
    {
        $window = $this->makeWindow();
        $window->setAmount('100');

        $cart = new Cart();
        $cart->addItem(new CartItem('Widget', 100, 1, 0));
        $window->setCart($cart);

        $fields = $window->getFormFields();
        $this->assertArrayHasKey('onpay_cart_items[0][name]', $fields);
        $this->assertSame('Widget', $fields['onpay_cart_items[0][name]']);
    }

    public function testGetFormFieldsPropagatesInvalidCartException(): void
    {
        // PaymentWindow itself throws nothing; the only throw path is the cart
        // validation propagating InvalidCartException on an amount mismatch.
        $window = $this->makeWindow();
        $window->setAmount('100');

        $cart = new Cart();
        $cart->addItem(new CartItem('Widget', 999, 1, 0)); // cart total 999 != 100
        $window->setCart($cart);

        $this->expectException(InvalidCartException::class);
        $window->getFormFields();
    }

    // ---------------------------------------------------------------------
    // isValid() branches
    // ---------------------------------------------------------------------

    public function testIsValidFalseWhenAmountMissingAndNotSubscription(): void
    {
        $window = new PaymentWindow();
        $this->assertFalse($window->isValid());
    }

    public function testIsValidFalseWhenRequiredFieldMissing(): void
    {
        $window = new PaymentWindow();
        $window->setAmount('100'); // pass the first guard, reach required-field loop
        $window->setGatewayId('gw');
        $window->setCurrency('DKK');
        $window->setReference('ref');
        // acceptUrl intentionally left null
        $this->assertFalse($window->isValid());
    }

    public function testIsValidTrueWhenAllRequiredPresent(): void
    {
        $window = $this->makeWindow();
        $this->assertTrue($window->isValid());
    }

    public function testIsValidTrueForSubscriptionWithoutAmount(): void
    {
        $window = new PaymentWindow();
        $window->setType('subscription');
        $window->setGatewayId('gw');
        $window->setCurrency('DKK');
        $window->setReference('ref');
        $window->setAcceptUrl('https://accept');
        // no amount set, but type is subscription
        $this->assertTrue($window->isValid());
    }

    // ---------------------------------------------------------------------
    // validatePayment()
    // ---------------------------------------------------------------------

    public function testValidatePaymentReturnsFalseWhenHmacMissing(): void
    {
        $window = new PaymentWindow();
        $window->setSecret('hmacsecret');
        $this->assertFalse($window->validatePayment(['onpay_uuid' => 'abc-123']));
    }

    public function testValidatePaymentReturnsTrueForCorrectHmac(): void
    {
        $window = new PaymentWindow();
        $window->setSecret('hmacsecret');

        $fields = [
            'onpay_uuid'     => 'abc-123',
            'onpay_number'   => '9001',
            'onpay_amount'   => '12300',
            'onpay_currency' => '208',
            'onpay_hmac_sha1' => '96dfc5291e1fdf96dd76737a9ac7b26c097a01b2',
        ];

        $this->assertTrue($window->validatePayment($fields));
    }

    public function testValidatePaymentReturnsFalseForWrongHmac(): void
    {
        $window = new PaymentWindow();
        $window->setSecret('hmacsecret');

        $fields = [
            'onpay_uuid'      => 'abc-123',
            'onpay_number'    => '9001',
            'onpay_amount'    => '12300',
            'onpay_currency'  => '208',
            'onpay_hmac_sha1' => 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeef',
        ];

        $this->assertFalse($window->validatePayment($fields));
    }

    public function testValidatePaymentIgnoresNonOnpayFields(): void
    {
        // Non-onpay_ fields are dropped, so an extra "random" key does not
        // affect the computed signature.
        $window = new PaymentWindow();
        $window->setSecret('hmacsecret');

        $fields = [
            'random'          => 'ignored',
            'onpay_uuid'      => 'abc-123',
            'onpay_number'    => '9001',
            'onpay_amount'    => '12300',
            'onpay_currency'  => '208',
            'onpay_hmac_sha1' => '96dfc5291e1fdf96dd76737a9ac7b26c097a01b2',
        ];

        $this->assertTrue($window->validatePayment($fields));
    }

    public function testValidatePaymentExcludesFieldWhoseNameMerelyContainsThePrefix(): void
    {
        // Prefix-anchored match: foo_onpay_bar (contains but does not START WITH
        // "onpay_") is NOT part of the signed set, mirroring the server. A signature
        // over only the strictly-prefixed fields validates even when foo_onpay_bar
        // is present in the callback.
        $window = new PaymentWindow();
        $window->setSecret('hmacsecret');

        $strictSubset = [
            'onpay_number' => '9001',
            'onpay_uuid'   => 'abc-123',
        ];
        ksort($strictSubset);
        $strictHmac = hash_hmac('sha1', strtolower(http_build_query($strictSubset)), 'hmacsecret');

        $fields = [
            'onpay_uuid'      => 'abc-123',
            'onpay_number'    => '9001',
            'foo_onpay_bar'   => 'sneaky',
            'onpay_hmac_sha1' => $strictHmac,
        ];

        $this->assertTrue($window->validatePayment($fields));
    }

    public function testValidatePaymentRejectsSignatureThatIncludedANonPrefixedField(): void
    {
        // The old loose strpos() check folded foo_onpay_bar into the signed set. A
        // signature computed that way (the previously-pinned value) is now rejected,
        // because foo_onpay_bar is correctly excluded.
        $window = new PaymentWindow();
        $window->setSecret('hmacsecret');

        $fields = [
            'onpay_uuid'      => 'abc-123',
            'onpay_number'    => '9001',
            'foo_onpay_bar'   => 'sneaky',
            'onpay_hmac_sha1' => '3d97d760c3ec47f52bdc2728a97f6afb285d5a80',
        ];

        $this->assertFalse($window->validatePayment($fields));
    }
}
