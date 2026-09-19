<?php

namespace Tests\Unit\Core;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Http\Discovery\ClassDiscovery;
use Http\Discovery\Exception\NotFoundException;
use OnPay\OnPayAPI;
use OnPay\TokenStorageInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

/**
 * Covers the one HTTP-client resolution branch not exercised by
 * {@see \Tests\Unit\PluggableHttpClientTest}: a PSR-18 client is injected but no PSR-17
 * factory can be discovered, so resolveHttpClient() must throw a helpful
 * InvalidArgumentException wrapping the discovery NotFoundException.
 */
class HttpClientResolutionTest extends TestCase
{
    public function testInjectedClientWithNoDiscoverableFactoryThrows(): void
    {
        $psrClient = $this->createMock(ClientInterface::class);
        $psrClient->method('sendRequest')->willReturn(new GuzzleResponse(200));

        // Force PSR-17 factory discovery to find nothing.
        $originalStrategies = (new \ReflectionProperty(ClassDiscovery::class, 'strategies'))->getValue();

        try {
            ClassDiscovery::setStrategies([]);
            ClassDiscovery::clearCache();

            $caught = null;
            try {
                // Client injected, factories left null => must attempt discovery and fail.
                new OnPayAPI($this->validTokenStorage(), $this->options(), $psrClient);
            } catch (\InvalidArgumentException $e) {
                $caught = $e;
            }

            $this->assertNotNull($caught, 'Expected InvalidArgumentException');
            $this->assertStringContainsString('no PSR-17 request/stream factory could be found', $caught->getMessage());
            $this->assertInstanceOf(NotFoundException::class, $caught->getPrevious());
        } finally {
            ClassDiscovery::setStrategies($originalStrategies);
            ClassDiscovery::clearCache();
        }
    }

    /**
     * @return array<string,string>
     */
    private function options(): array
    {
        return [
            'client_id' => 'test_client_id',
            'redirect_uri' => 'test_redirect_uri',
            'base_uri' => 'https://api.onpay.invalid',
            'base_authorize_uri' => 'https://manage.onpay.invalid',
        ];
    }

    private function validTokenStorage(): TokenStorageInterface
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        return $tokenStorage;
    }
}
