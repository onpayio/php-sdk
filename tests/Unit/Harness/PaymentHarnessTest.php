<?php

namespace Tests\Unit\Harness;

use OnPay\API\Payment\SimplePayment;
use OnPay\API\PaymentWindow;
use Tests\Support\ApiTestCase;
use Tests\Support\FixtureLoader;

/**
 * End-to-end harness coverage for PaymentService::createNewPayment.
 */
class PaymentHarnessTest extends ApiTestCase
{
    public function testCreateNewPaymentSendsPostAndParsesSimplePayment(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('payment/created'), 200, 'POST');

        $api = $this->createApi();

        $window = new PaymentWindow();
        $window->setSecret('test_hmac_secret');
        $window->setCurrency('DKK');
        $window->setAmount('12500');
        $window->setReference('order-4242');
        $window->setWebsite('https://shop.test');
        $window->setAcceptUrl('https://shop.test/accept');

        $payment = $api->payment()->createNewPayment($window);

        $request = $this->http->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(self::BASE_URI . '/v1/payment/create', (string) $request->getUri());
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));

        // Payload carries the required, cleaned fields.
        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame(12500, $body['amount']);
        $this->assertSame('DKK', $body['currency']);
        $this->assertSame('order-4242', $body['reference']);

        // SimplePayment is built from the WHOLE envelope (data + links.payment_window).
        $this->assertInstanceOf(SimplePayment::class, $payment);
        $this->assertSame('9c8b7a65-4321-4dcb-a987-0e02b2c3d479', $payment->getUuid());
        $this->assertSame(12500, $payment->getAmount());
        $this->assertSame(
            'https://onpay.io/window/v3/9c8b7a65-4321-4dcb-a987-0e02b2c3d479',
            $payment->getPaymentWindowLink()
        );
    }
}
