<?php

namespace Tests\Unit\PaymentService;

use OnPay\API\Exception\InvalidFormatException;
use OnPay\API\Exception\MissingDataException;
use OnPay\API\Payment\SimplePayment;
use OnPay\API\PaymentWindow;
use OnPay\API\PaymentWindow\Cart;
use OnPay\API\PaymentWindow\CartItem;
use OnPay\API\PaymentWindow\PaymentInfo;
use Tests\Support\ApiTestCase;
use Tests\Support\FixtureLoader;

/**
 * Focused coverage for {@see \OnPay\API\PaymentService} — specifically the builder
 * branches the minimal harness test does not exercise: the PaymentInfo / account /
 * billing / shipping / phone blocks and the Cart (items + shipping + handling +
 * discount) block, plus the guard/validation paths.
 *
 * These tests pin CURRENT behaviour (including a couple of quirks noted inline), and
 * assert the OUTGOING request body as well as the parsed
 * SimplePayment result.
 */
class PaymentServiceTest extends ApiTestCase
{
    /**
     * A fully populated payment: PaymentInfo (top-level + account + billing + shipping +
     * phone) and a Cart with two items, shipping, handling and a cart discount. Asserts
     * every builder block reaches the wire, and that the response parses to SimplePayment.
     */
    public function testFullPaymentBuildsInfoAccountAndCartOntoRequest(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('payment/created'), 200, 'POST');

        $api = $this->createApi();

        $window = new PaymentWindow();
        $window->setSecret('test_hmac_secret');
        $window->setCurrency('DKK');
        // Amount MUST equal the computed cart total or Cart::throwOnInvalid rejects it:
        // items 10500 + shipping 1000 - shipping discount 100 + handling 300 - cart discount 200 = 11500.
        $window->setAmount('11500');
        $window->setReference('order-4242');
        $window->setWebsite('https://shop.test');
        $window->setAcceptUrl('https://shop.test/accept');

        $info = new PaymentInfo();
        // Top-level info fields.
        $info->setName('John Doe');
        $info->setEmail('customer@shop.test');
        $info->setAddressIdenticalShipping('Y');
        $info->setDeliveryEmail('delivery@shop.test');
        $info->setDeliveryTimeFrame(PaymentInfo::DELIVERY_TIMEFRAME_SAMEDAY);
        $info->setGiftCardAmount('500');
        $info->setGiftCardCount('1');
        $info->setPreorder('N');
        $info->setPreorderDate('2021-02-02');
        $info->setReorder('N');
        $info->setShippingMethod(PaymentInfo::SHIPPING_METHOD_VERIFIED_ADDRESS);
        // Account block.
        $info->setAccountId('user-123');
        $info->setAccountDateCreated('2021-01-01');
        $info->setAccountDateChange('2021-03-03');
        $info->setAccountDatePasswordChange('2021-04-04');
        $info->setAccountPurchases('7');
        $info->setAccountAttempts('2');
        $info->setAccountShippingFirstUseDate('2021-05-05');
        $info->setAccountShippingIdenticalName('Y');
        $info->setAccountSuspicious('N');
        $info->setAccountAttemptsDay('1');
        $info->setAccountAttemptsYear('9');
        // Billing block.
        $info->setBillingAddressCity('Copenhagen');
        $info->setBillingAddressCountry('208');
        $info->setBillingAddressLine1('Main street 1');
        $info->setBillingAddressLine2('Floor 2');
        $info->setBillingAddressLine3('Door 3');
        $info->setBillingAddressPostalCode('1000');
        $info->setBillingAddressState('DK');
        // Shipping block.
        $info->setShippingAddressCity('Aarhus');
        $info->setShippingAddressCountry('208');
        $info->setShippingAddressLine1('Second street 4');
        $info->setShippingAddressLine2('Floor 5');
        $info->setShippingAddressLine3('Door 6');
        $info->setShippingAddressPostalCode('8000');
        $info->setShippingAddressState('DK');
        // Phone block.
        $info->setPhoneHome('45', '12345678');
        $info->setPhoneMobile('45', '87654321');
        $info->setPhoneWork('45', '11112222');
        $window->setInfo($info);

        $cart = new Cart();
        $cart->addItem(new CartItem('T-shirt', 5000, 2, 1000, 'Cotton tee', 'SKU-1', 'pcs', '00000001', CartItem::TYPE_PHYSICAL));
        $cart->addItem(new CartItem('Sticker', 500, 1, 100));
        $cart->setShipping(1000, 200, 100, 'Standard');
        $cart->setHandling(300, 60, 'Gift wrap');
        $cart->setDiscount(200);
        $window->setCart($cart);

        $payment = $api->payment()->createNewPayment($window);

        $request = $this->http->getLastRequest();
        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(self::BASE_URI . '/v1/payment/create', (string) $request->getUri());

        $body = json_decode((string) $request->getBody(), true);

        // Core fields.
        $this->assertSame(11500, $body['amount']); // intval() applied.
        $this->assertSame('DKK', $body['currency']);
        $this->assertSame('order-4242', $body['reference']);
        $this->assertSame('https://shop.test', $body['website']);
        $this->assertSame('https://shop.test/accept', $body['accepturl']);
        // QUIRK: testmode is unset yet always present as false — boolval(null) is false and
        // cleanData only prunes strict nulls, so this survives. (Pins current behaviour.)
        $this->assertArrayHasKey('testmode', $body);
        $this->assertFalse($body['testmode']);

        // Top-level info fields.
        $this->assertSame('John Doe', $body['info']['name']);
        $this->assertSame('customer@shop.test', $body['info']['email']);
        $this->assertSame('Y', $body['info']['address_identical_shipping']);
        $this->assertSame('delivery@shop.test', $body['info']['delivery_email']);
        $this->assertSame('02', $body['info']['delivery_time_frame']);
        $this->assertSame('500', $body['info']['gift_card_amount']);
        $this->assertSame('1', $body['info']['gift_card_count']);
        $this->assertSame('N', $body['info']['preorder']);
        $this->assertSame('2021-02-02', $body['info']['preorder_date']);
        $this->assertSame('N', $body['info']['reorder']);
        $this->assertSame('02', $body['info']['shipping_method']);

        // Account block.
        $this->assertSame('user-123', $body['info']['account']['id']);
        $this->assertSame('2021-01-01', $body['info']['account']['date_created']);
        $this->assertSame('2021-03-03', $body['info']['account']['date_change']);
        $this->assertSame('2021-04-04', $body['info']['account']['date_password_change']);
        $this->assertSame('7', $body['info']['account']['purchases']);
        $this->assertSame('2', $body['info']['account']['attempts']);
        $this->assertSame('2021-05-05', $body['info']['account']['shipping_first_use_date']);
        $this->assertSame('Y', $body['info']['account']['shipping_identical_name']);
        $this->assertSame('N', $body['info']['account']['suspicious']);
        $this->assertSame('1', $body['info']['account']['attempts_day']);
        $this->assertSame('9', $body['info']['account']['attempts_year']);

        // Billing block.
        $this->assertSame('Copenhagen', $body['info']['billing']['address_city']);
        $this->assertSame('208', $body['info']['billing']['address_country']);
        $this->assertSame('Main street 1', $body['info']['billing']['address_line1']);
        $this->assertSame('Floor 2', $body['info']['billing']['address_line2']);
        $this->assertSame('Door 3', $body['info']['billing']['address_line3']);
        $this->assertSame('1000', $body['info']['billing']['address_postal_code']);
        $this->assertSame('DK', $body['info']['billing']['address_state']);

        // Shipping block.
        $this->assertSame('Aarhus', $body['info']['shipping']['address_city']);
        $this->assertSame('208', $body['info']['shipping']['address_country']);
        $this->assertSame('Second street 4', $body['info']['shipping']['address_line1']);
        $this->assertSame('Floor 5', $body['info']['shipping']['address_line2']);
        $this->assertSame('Door 6', $body['info']['shipping']['address_line3']);
        $this->assertSame('8000', $body['info']['shipping']['address_postal_code']);
        $this->assertSame('DK', $body['info']['shipping']['address_state']);

        // Phone block.
        $this->assertSame('45', $body['info']['phone']['home_cc']);
        $this->assertSame('12345678', $body['info']['phone']['home_number']);
        $this->assertSame('45', $body['info']['phone']['mobile_cc']);
        $this->assertSame('87654321', $body['info']['phone']['mobile_number']);
        $this->assertSame('45', $body['info']['phone']['work_cc']);
        $this->assertSame('11112222', $body['info']['phone']['work_number']);

        // Cart block. shipping/handling are serialized from their value objects.
        $this->assertSame(1000, $body['cart']['shipping']['price']);
        $this->assertSame(200, $body['cart']['shipping']['tax']);
        $this->assertSame(100, $body['cart']['shipping']['discount']);
        $this->assertSame('Standard', $body['cart']['shipping']['name']);
        $this->assertSame(300, $body['cart']['handling']['price']);
        $this->assertSame(60, $body['cart']['handling']['tax']);
        $this->assertSame('Gift wrap', $body['cart']['handling']['name']);
        $this->assertSame(200, $body['cart']['discount']);

        $this->assertCount(2, $body['cart']['items']);
        $first = $body['cart']['items'][0];
        $this->assertSame('T-shirt', $first['name']);
        $this->assertSame('Cotton tee', $first['description']);
        $this->assertSame('SKU-1', $first['sku']);
        $this->assertSame(5000, $first['price']);
        $this->assertSame(2, $first['quantity']);
        $this->assertSame(1000, $first['tax']);
        $this->assertSame('pcs', $first['quantity_unit']);
        $this->assertSame('00000001', $first['global_trade_item_number']);
        $this->assertSame('physical', $first['type']);
        $second = $body['cart']['items'][1];
        $this->assertSame('Sticker', $second['name']);
        $this->assertSame(500, $second['price']);
        // Optional item fields left unset are absent (CartItem::getFields prunes them).
        $this->assertArrayNotHasKey('description', $second);
        $this->assertArrayNotHasKey('type', $second);

        // Response parses to SimplePayment from the whole envelope.
        $this->assertInstanceOf(SimplePayment::class, $payment);
        $this->assertSame('9c8b7a65-4321-4dcb-a987-0e02b2c3d479', $payment->getUuid());
        $this->assertSame(12500, $payment->getAmount());
        $this->assertSame('DKK', $payment->getCurrency());
        $this->assertSame('1789999999', $payment->getExpiration());
        $this->assertSame('en', $payment->getLanguage());
        $this->assertSame('card', $payment->getMethod());
        $this->assertSame(
            'https://onpay.io/window/v3/9c8b7a65-4321-4dcb-a987-0e02b2c3d479',
            $payment->getPaymentWindowLink()
        );
    }

    /**
     * A minimal Cart (shipping only, no shipping discount/name, no handling, no cart
     * discount, one item) with NO PaymentInfo. This exercises the info nested-array
     * "empty -> pruned" branch of cleanData (the false side of `count($item) > 0`) and
     * pins a serialization quirk in the shipping value object.
     */
    public function testCartOnlyShippingSerializesValueObjectWithNullDefaults(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('payment/created'), 200, 'POST');

        $api = $this->createApi();

        $window = new PaymentWindow();
        $window->setSecret('test_hmac_secret');
        $window->setCurrency('DKK');
        // items 9000 + shipping 1000 = 10000.
        $window->setAmount('10000');
        $window->setReference('order-5001');
        $window->setWebsite('https://shop.test');
        $window->setAcceptUrl('https://shop.test/accept');

        $cart = new Cart();
        $cart->addItem(new CartItem('Chair', 9000, 1, 1000));
        $cart->setShipping(1000, 200); // no discount, no name.
        $window->setCart($cart);

        $api->payment()->createNewPayment($window);

        $body = json_decode((string) $this->http->getLastRequest()->getBody(), true);

        // No PaymentInfo was set: every info.* value is null, the nested arrays collapse to
        // empty and cleanData prunes the whole `info` key. (False branch of count($item) > 0.)
        $this->assertArrayNotHasKey('info', $body);

        // QUIRK: cart.shipping is emitted as a CartShipping VALUE
        // OBJECT, and cleanData does not recurse into objects — so its unset `discount` and
        // `name` serialize as explicit nulls, unlike every pruned scalar elsewhere.
        $this->assertSame(1000, $body['cart']['shipping']['price']);
        $this->assertSame(200, $body['cart']['shipping']['tax']);
        $this->assertArrayHasKey('discount', $body['cart']['shipping']);
        $this->assertNull($body['cart']['shipping']['discount']);
        $this->assertArrayHasKey('name', $body['cart']['shipping']);
        $this->assertNull($body['cart']['shipping']['name']);

        // No handling / cart discount set -> absent.
        $this->assertArrayNotHasKey('handling', $body['cart']);
        $this->assertArrayNotHasKey('discount', $body['cart']);
        $this->assertCount(1, $body['cart']['items']);
    }

    /**
     * An empty PaymentWindow has none of the required fields, so validatePaymentData
     * collects one message per missing field and throws. Pins the exact message format.
     */
    public function testMissingRequiredFieldsThrowsMissingDataException(): void
    {
        $api = $this->createApi();

        $this->expectException(MissingDataException::class);
        $this->expectExceptionMessage(
            "Missing required field in payment request: currency\n" .
            "Missing required field in payment request: amount\n" .
            "Missing required field in payment request: reference\n" .
            "Missing required field in payment request: website"
        );

        $api->payment()->createNewPayment(new PaymentWindow());
    }

    /**
     * The instanceof guard must run before the argument is dereferenced.
     */
    public function testNonPaymentWindowThrowsInvalidFormatException(): void
    {
        $api = $this->createApi();

        $this->expectException(InvalidFormatException::class);
        $this->expectExceptionMessage('Creating a payment request requires a valid PaymentWindow object.');

        $api->payment()->createNewPayment(new \stdClass());
    }
}
