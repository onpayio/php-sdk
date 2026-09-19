<?php

namespace Tests\Unit\Harness;

use GuzzleHttp\Psr7\Response;
use OnPay\API\Exception\ApiException;
use OnPay\API\Exception\ConnectionException;
use OnPay\API\Exception\TokenException;
use Psr\Http\Client\ClientExceptionInterface;
use Tests\Support\ApiTestCase;
use Tests\Support\FixtureLoader;

/**
 * Proves the harness exercises OnPayAPI::handleResponse() error branches and transport
 * failures without a network.
 */
class ErrorHandlingHarnessTest extends ApiTestCase
{
    public function testNotFoundBodyIsParsedIntoApiException(): void
    {
        // 404 with a JSON error body + the exact Content-Type the SDK requires.
        $this->http->willReturnJson(FixtureLoader::load('error/not-found'), 404, 'GET');

        $api = $this->createApi();

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(404);
        // handleResponse() overrides the message to "Not found" on a 404.
        $this->expectExceptionMessage('Not found');

        $api->transaction()->getTransaction('does-not-exist');
    }

    public function testInvalidJsonOn200BecomesApiException(): void
    {
        $this->http->willReturn(
            new Response(200, ['Content-Type' => 'application/json'], '{invalid json'),
            'GET'
        );

        $api = $this->createApi();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Failed to decode JSON body-response');

        $api->ping();
    }

    public function testNonArrayJsonOn200BecomesApiException(): void
    {
        $this->http->willReturn(
            new Response(200, ['Content-Type' => 'application/json'], json_encode('a bare string')),
            'GET'
        );

        $api = $this->createApi();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Expected a JSON object in the response body');

        $api->ping();
    }

    public function testForbiddenBecomesTokenException(): void
    {
        $this->http->willReturn(
            new Response(403, ['Content-Type' => 'application/json'], json_encode([
                'errors' => [['message' => 'Access denied']],
            ])),
            'GET'
        );

        $api = $this->createApi();

        $this->expectException(TokenException::class);
        $this->expectExceptionCode(403);
        $api->transaction()->getTransaction('123');
    }

    public function testTransportFailureBecomesConnectionException(): void
    {
        // A PSR-18 client that throws is mapped through CurlException to ConnectionException.
        $throwingClient = $this->createMock(\Psr\Http\Client\ClientInterface::class);
        $throwingClient->method('sendRequest')->willThrowException(
            new class ('boom') extends \RuntimeException implements ClientExceptionInterface {}
        );

        $factory = new \GuzzleHttp\Psr7\HttpFactory();
        $api = new \OnPay\OnPayAPI(
            $this->validTokenStorage(),
            [
                'client_id' => self::CLIENT_ID,
                'redirect_uri' => self::REDIRECT_URI,
                'base_uri' => self::BASE_URI,
                'base_authorize_uri' => self::BASE_AUTHORIZE_URI,
            ],
            null,
            $throwingClient,
            $factory,
            $factory
        );

        $this->expectException(ConnectionException::class);
        $api->transaction()->getTransaction('123');
    }
}
