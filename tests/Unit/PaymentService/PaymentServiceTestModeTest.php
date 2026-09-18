<?php

namespace Tests\Unit\PaymentService;

use OnPay\API\PaymentWindow;
use Tests\Support\ApiTestCase;
use Tests\Support\FixtureLoader;

/**
 * Focused coverage for the testmode boolval() cast in PaymentService::buildCreatePaymentData.
 *
 * The false side is already pinned by {@see PaymentServiceTest} (an unset testmode arrives
 * as boolval(null) === false). This pins the TRUE side, and does so with a NON-boolean
 * input (1) on purpose: PaymentWindow historically allows mixed testmode values, so a
 * literal `true` would make the assertion pass with or without the boolval() cast. Feeding
 * 1 makes the cast load-bearing — boolval(1) is JSON `true`, whereas the raw 1 would
 * serialize as the integer 1 and fail assertSame(true, ...).
 */
class PaymentServiceTestModeTest extends ApiTestCase
{
    public function testTruthyTestModeIsCastToBooleanTrueOnTheWire(): void
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
        // Non-boolean truthy value: proves the boolval() cast actually runs.
        $window->setTestMode(1);

        $api->payment()->createNewPayment($window);

        $body = json_decode((string) $this->http->getLastRequest()->getBody(), true);

        $this->assertArrayHasKey('testmode', $body);
        $this->assertSame(true, $body['testmode']);
    }
}
