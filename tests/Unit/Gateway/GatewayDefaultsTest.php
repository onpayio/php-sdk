<?php

namespace Tests\Unit\Gateway;

use OnPay\API\Gateway\Information;
use OnPay\API\Gateway\PaymentWindowIntegrationSettings;
use PHPUnit\Framework\TestCase;

/**
 * Direct coverage for the two gateway value objects' default branches.
 *
 * The gateway harness fixtures always carry both fields, so the isset()-false side (the
 * default) is never exercised there. Each pair below pins present vs absent with distinct
 * values so both sides of the read are proven.
 */
class GatewayDefaultsTest extends TestCase
{
    public function testInformationReadsGatewayIdWhenPresent(): void
    {
        $information = new Information(['gateway_id' => 'gw-abc-123']);

        $this->assertSame('gw-abc-123', $information->gatewayId);
    }

    public function testInformationGatewayIdDefaultsToNull(): void
    {
        $information = new Information([]);

        $this->assertNull($information->gatewayId);
    }

    public function testIntegrationSettingsReadsSecretWhenPresent(): void
    {
        $settings = new PaymentWindowIntegrationSettings(['secret' => 's3cr3t']);

        $this->assertSame('s3cr3t', $settings->secret);
    }

    public function testIntegrationSettingsSecretDefaultsToEmptyString(): void
    {
        $settings = new PaymentWindowIntegrationSettings([]);

        $this->assertSame('', $settings->secret);
    }
}
