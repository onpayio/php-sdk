<?php

namespace Tests\Unit\Gateway;

use OnPay\API\Exception\ApiException;
use OnPay\API\Gateway\Information;
use OnPay\API\Gateway\PaymentWindowIntegrationSettings;
use PHPUnit\Framework\TestCase;

/**
 * Direct coverage for the two gateway value objects.
 *
 * `gateway_id` and `secret` are always present on their respective endpoints, so both are
 * non-nullable and the constructor throws {@see ApiException} when the field is absent.
 */
class GatewayDefaultsTest extends TestCase
{
    public function testInformationReadsGatewayIdWhenPresent(): void
    {
        $information = new Information(['gateway_id' => 'gw-abc-123']);

        $this->assertSame('gw-abc-123', $information->gatewayId);
    }

    public function testInformationThrowsWhenGatewayIdMissing(): void
    {
        $this->expectException(ApiException::class);
        new Information([]);
    }

    public function testIntegrationSettingsReadsSecretWhenPresent(): void
    {
        $settings = new PaymentWindowIntegrationSettings(['secret' => 's3cr3t']);

        $this->assertSame('s3cr3t', $settings->secret);
    }

    public function testIntegrationSettingsThrowsWhenSecretMissing(): void
    {
        $this->expectException(ApiException::class);
        new PaymentWindowIntegrationSettings([]);
    }
}
