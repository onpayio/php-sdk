<?php

namespace Tests\Unit\Harness;

use OnPay\API\Gateway\Information;
use OnPay\API\Gateway\PaymentWindowDesignCollection;
use OnPay\API\Gateway\PaymentWindowIntegrationSettings;
use Tests\Support\ApiTestCase;
use Tests\Support\FixtureLoader;

/**
 * End-to-end harness coverage for GatewayService, asserting the outgoing request
 * (method + full path) and the parsed result for every call.
 */
class GatewayHarnessTest extends ApiTestCase
{
    public function testGetInformationParsesInformation(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('gateway/information'), 200, 'GET');

        $api = $this->createApi();
        $information = $api->gateway()->getInformation();

        $request = $this->http->getLastRequest();
        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame(self::BASE_URI . '/v1/gateway/information', (string) $request->getUri());

        $this->assertInstanceOf(Information::class, $information);
        $this->assertSame('A5KM3QX7B', $information->gatewayId);
    }

    public function testGetPaymentWindowIntegrationSettingsParsesSecret(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('gateway/integration'), 200, 'GET');

        $api = $this->createApi();
        $settings = $api->gateway()->getPaymentWindowIntegrationSettings();

        $request = $this->http->getLastRequest();
        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame(
            self::BASE_URI . '/v1/gateway/window/v3/integration',
            (string) $request->getUri()
        );

        $this->assertInstanceOf(PaymentWindowIntegrationSettings::class, $settings);
        $this->assertSame('test_hmac_secret', $settings->secret);
    }

    public function testGetPaymentWindowDesignsParsesCollection(): void
    {
        $this->http->willReturnJson(FixtureLoader::load('gateway/design-collection'), 200, 'GET');

        $api = $this->createApi();
        $designs = $api->gateway()->getPaymentWindowDesigns();

        $request = $this->http->getLastRequest();
        $this->assertCount(1, $this->http->getRecordedRequests());
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame(
            self::BASE_URI . '/v1/gateway/window/v3/design/',
            (string) $request->getUri()
        );

        $this->assertInstanceOf(PaymentWindowDesignCollection::class, $designs);
        $this->assertCount(2, $designs->paymentWindowDesigns);
        $this->assertSame('default', $designs->paymentWindowDesigns[0]->name);
        $this->assertSame('checkout-dark', $designs->paymentWindowDesigns[1]->name);
    }

    public function testGetPaymentWindowDesignsMapsNonArrayEntryToEmptyDesign(): void
    {
        $this->http->willReturnJson(['data' => [['name' => 'default'], 'not-an-array']], 200, 'GET');

        $api = $this->createApi();
        $designs = $api->gateway()->getPaymentWindowDesigns();

        $this->assertCount(2, $designs->paymentWindowDesigns);
        $this->assertSame('default', $designs->paymentWindowDesigns[0]->name);
        $this->assertNull($designs->paymentWindowDesigns[1]->name);
    }
}
