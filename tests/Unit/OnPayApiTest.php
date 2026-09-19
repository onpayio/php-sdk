<?php

namespace Tests\Unit;

use OnPay\OAuth\Client\Provider;
use OnPay\OnPayAPI;
use OnPay\TokenStorageInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class OnPayApiTest extends TestCase {
    /** @throws Exception */
    public function testInitializeThrowsOnMissingRequiredParameterClientId(): void {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn('test_token');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Required options not defined: client_id');
        new OnPayAPI($tokenStorage, ['redirect_uri' => 'test_uri']);
    }

    /** @throws Exception */
    public function testInitializeThrowsOnMissingRequiredParameterRedirectUri(): void {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn('test_token');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Required options not defined: redirect_uri');
        new OnPayAPI($tokenStorage, ['client_id' => 'test_id']);
    }

    /** @throws Exception */
    public function testInitializeApi(): void {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn('test_token');
        $api = new OnPayAPI($tokenStorage, ['client_id' => 'test_id', 'redirect_uri' => 'test_uri']);
        // A successful construct exposes the default platform and default authorize endpoint.
        $this->assertSame('php-sdk/' . OnPayAPI::SDK_VERSION, $api->getPlatform());
        $this->assertSame('https://manage.onpay.io/oauth2/authorize', $this->getAuthorizationEndpoint($api));
    }

    /** @throws Exception */
    public function testInitializeApiWithLegacyNumericStringGatewayId(): void {
        $api = new OnPayAPI($this->createMock(TokenStorageInterface::class), [
            'client_id' => 'test_id',
            'redirect_uri' => 'test_uri',
            'gateway_id' => '1234',
        ]);
        $this->assertStringContainsString('/1234/oauth2/authorize', $this->getAuthorizationEndpoint($api));
    }

    /** @throws Exception */
    public function testInitializeApiWithLegacyNumericIntGatewayId(): void {
        $api = new OnPayAPI($this->createMock(TokenStorageInterface::class), [
            'client_id' => 'test_id',
            'redirect_uri' => 'test_uri',
            'gateway_id' => 1234,
        ]);
        $this->assertStringContainsString('/1234/oauth2/authorize', $this->getAuthorizationEndpoint($api));
    }

    /** @throws Exception */
    public function testInitializeApiWithNewAlphanumericGatewayId(): void {
        $api = new OnPayAPI($this->createMock(TokenStorageInterface::class), [
            'client_id' => 'test_id',
            'redirect_uri' => 'test_uri',
            'gateway_id' => 'A5KM3QX7B',
        ]);
        $this->assertStringContainsString('/A5KM3QX7B/oauth2/authorize', $this->getAuthorizationEndpoint($api));
    }

    /** @throws Exception */
    public function testInitializeThrowsOnEmptyGatewayId(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('gateway_id must be a non-empty alphanumeric value');
        new OnPayAPI($this->createMock(TokenStorageInterface::class), [
            'client_id' => 'test_id',
            'redirect_uri' => 'test_uri',
            'gateway_id' => '',
        ]);
    }

    /** @throws Exception */
    public function testInitializeThrowsOnLowercaseGatewayId(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('gateway_id must be a non-empty alphanumeric value');
        new OnPayAPI($this->createMock(TokenStorageInterface::class), [
            'client_id' => 'test_id',
            'redirect_uri' => 'test_uri',
            'gateway_id' => 'a5km3qx7b',
        ]);
    }

    /** @throws Exception */
    public function testInitializeThrowsOnGatewayIdWithHyphen(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('gateway_id must be a non-empty alphanumeric value');
        new OnPayAPI($this->createMock(TokenStorageInterface::class), [
            'client_id' => 'test_id',
            'redirect_uri' => 'test_uri',
            'gateway_id' => 'A5-KM3',
        ]);
    }

    /** @throws Exception */
    public function testInitializeThrowsOnGatewayIdWithSpace(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('gateway_id must be a non-empty alphanumeric value');
        new OnPayAPI($this->createMock(TokenStorageInterface::class), [
            'client_id' => 'test_id',
            'redirect_uri' => 'test_uri',
            'gateway_id' => 'A5 KM3',
        ]);
    }

    private function getAuthorizationEndpoint(OnPayAPI $api): string {
        // Private members are reflection-accessible without setAccessible() on PHP 8.1+.
        /** @var Provider $provider */
        $provider = (new \ReflectionProperty(OnPayAPI::class, 'oauth2Provider'))->getValue($api);
        return $provider->getAuthorizationEndpoint();
    }
}
