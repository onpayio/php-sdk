<?php

namespace Tests\Unit\Log;

use GuzzleHttp\Psr7\HttpFactory;
use OnPay\API\Exception\ApiException;
use OnPay\API\Exception\ConnectionException;
use OnPay\API\Exception\TokenException;
use OnPay\Http\LoggingHttpClient;
use OnPay\Log\ErrorLogLogger;
use OnPay\OnPayAPI;
use OnPay\TokenStorageInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Tests\Support\ApiTestCase;

/**
 * Drives the real OnPayAPI through the harness with an injected PSR-3 logger and
 * pins what the SDK logs (and, above all, what it never logs: bearer tokens, OAuth
 * material, the payment window secret).
 */
class LoggingTest extends ApiTestCase
{
    // ---------------------------------------------------------------------
    // Wiring
    // ---------------------------------------------------------------------

    public function testInjectedLoggerIsUsedAndTransportIsWrapped(): void
    {
        $api = $this->createApi();

        $this->assertSame($this->logger, (new \ReflectionProperty(OnPayAPI::class, 'logger'))->getValue($api));
        $this->assertInstanceOf(
            LoggingHttpClient::class,
            (new \ReflectionProperty(OnPayAPI::class, 'httpClient'))->getValue($api)
        );
    }

    public function testFallsBackToErrorLogLoggerWhenNoneInjected(): void
    {
        $factory = new HttpFactory();
        $api = new OnPayAPI($this->validTokenStorage(), [
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT_URI,
            'base_uri' => self::BASE_URI,
            'base_authorize_uri' => self::BASE_AUTHORIZE_URI,
        ], null, $this->http, $factory, $factory);

        $logger = (new \ReflectionProperty(OnPayAPI::class, 'logger'))->getValue($api);
        $this->assertInstanceOf(ErrorLogLogger::class, $logger);
        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function testNullLoggerSilencesTheSdk(): void
    {
        $this->http->willReturnJson(['errors' => [['message' => 'boom']]], 400, 'GET', 'ping');
        $factory = new HttpFactory();
        $api = new OnPayAPI($this->validTokenStorage(), [
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT_URI,
            'base_uri' => self::BASE_URI,
            'base_authorize_uri' => self::BASE_AUTHORIZE_URI,
        ], null, $this->http, $factory, $factory, new NullLogger());

        // Only proves the NullLogger is accepted and nothing blows up; the fallback
        // logger's own output is covered in ErrorLogLoggerTest.
        $this->expectException(ApiException::class);
        $api->ping();
    }

    // ---------------------------------------------------------------------
    // Levels
    // ---------------------------------------------------------------------

    public function testSuccessfulRequestIsLoggedAtDebugWithoutPayload(): void
    {
        $this->http->willReturnJson(['data' => ['pong' => 'ok']], 200, 'GET', 'ping');

        $this->createApi()->ping();

        $this->assertCount(1, $this->logger->records);
        $record = $this->logger->records[0];
        $this->assertSame(LogLevel::DEBUG, $record['level']);
        $this->assertSame('OnPay {method} {uri} responded HTTP {status}', $record['message']);
        $this->assertSame([
            'method' => 'GET',
            'uri' => self::BASE_URI . '/v1/ping',
            'status' => 200,
        ], $record['context']);
    }

    public function testClientErrorIsLoggedAtWarning(): void
    {
        $this->http->willReturnJson(['errors' => [['message' => 'boom']]], 400, 'GET', 'transaction/x');

        try {
            $this->createApi()->get('transaction/x');
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
        }

        $records = $this->logger->recordsAtLevel(LogLevel::WARNING);
        $this->assertCount(1, $records);
        $this->assertSame(400, $records[0]['context']['status']);
        $this->assertSame(self::BASE_URI . '/v1/transaction/x', $records[0]['context']['uri']);
        $this->assertSame('{"errors":[{"message":"boom"}]}', $records[0]['context']['response_body']);
        $this->assertSame(['Content-Type' => 'application/json'], $records[0]['context']['response_headers']);
    }

    public function testServerErrorIsLoggedAtError(): void
    {
        $this->http->willReturnJson([], 503, 'GET', 'ping');

        try {
            $this->createApi()->ping();
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
        }

        $records = $this->logger->recordsAtLevel(LogLevel::ERROR);
        $this->assertCount(1, $records);
        $this->assertSame(503, $records[0]['context']['status']);
    }

    public function testTransportFailureIsLoggedAtErrorWithException(): void
    {
        $exception = new class ('network down') extends \RuntimeException implements ClientExceptionInterface {};
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willThrowException($exception);

        $factory = new HttpFactory();
        $api = new OnPayAPI($this->validTokenStorage(), [
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT_URI,
            'base_uri' => self::BASE_URI,
            'base_authorize_uri' => self::BASE_AUTHORIZE_URI,
        ], null, $client, $factory, $factory, $this->logger);

        try {
            $api->ping();
            $this->fail('Expected ConnectionException');
        } catch (ConnectionException $e) {
        }

        $records = $this->logger->recordsAtLevel(LogLevel::ERROR);
        $this->assertCount(1, $records);
        $this->assertSame('OnPay {method} {uri} failed: {reason}', $records[0]['message']);
        $this->assertSame('network down', $records[0]['context']['reason']);
        // The PSR-18 exception passes through the transport untouched.
        $this->assertSame($exception, $records[0]['context']['exception']);
        $this->assertArrayNotHasKey('response_body', $records[0]['context']);
    }

    // ---------------------------------------------------------------------
    // Redaction — the point of the ticket
    // ---------------------------------------------------------------------

    public function testBearerTokenNeverAppearsInLog(): void
    {
        $this->http->willReturnJson(['errors' => [['message' => 'Access denied']]], 403, 'GET', 'ping');

        try {
            $this->createApi()->ping();
            $this->fail('Expected TokenException');
        } catch (TokenException $e) {
        }

        $dump = $this->logger->dump();
        $this->assertStringNotContainsString(self::ACCESS_TOKEN, $dump);
        $this->assertStringNotContainsString('Bearer', $dump);

        $record = $this->logger->recordsAtLevel(LogLevel::WARNING)[0];
        $this->assertSame('[redacted]', $record['context']['request_headers']['Authorization']);
        // Non-sensitive headers survive so the record is still useful for debugging.
        $this->assertArrayHasKey('User-Agent', $record['context']['request_headers']);
    }

    public function testOAuthRefreshRoundTripNeverLeaksTokens(): void
    {
        // Expired access token forces a refresh; make the token endpoint fail so the
        // exchange (form-encoded body carrying the refresh token, Basic-auth header) is
        // logged, then assert none of the secrets made it through.
        $this->http->willReturnJson([
            'error' => 'server_error',
            'access_token' => 'leaked_new_access_token',
        ], 500, 'POST', 'oauth2/access_token');

        try {
            $this->createApi([], $this->expiredTokenStorage())->ping();
            $this->fail('Expected TokenException');
        } catch (TokenException $e) {
        }

        $records = $this->logger->recordsAtLevel(LogLevel::ERROR);
        $this->assertCount(1, $records);
        $context = $records[0]['context'];

        $this->assertSame(self::BASE_URI . '/oauth2/access_token', $context['uri']);
        $this->assertSame('[redacted]', $context['request_headers']['Authorization']);
        $this->assertSame(
            'client_id=test_client_id&redirect_uri=https%3A%2F%2Fexample.test%2Fredirect&grant_type=refresh_token&refresh_token=%5Bredacted%5D&scope=full',
            $context['request_body']
        );
        $this->assertSame('{"error":"server_error","access_token":"[redacted]"}', $context['response_body']);

        $dump = $this->logger->dump();
        $this->assertStringNotContainsString('test_refresh_token', $dump);
        $this->assertStringNotContainsString(self::ACCESS_TOKEN, $dump);
        $this->assertStringNotContainsString('leaked_new_access_token', $dump);
        $this->assertStringNotContainsString(base64_encode(self::CLIENT_ID . ':'), $dump);
    }

    public function testPaymentWindowSecretNeverAppearsInLog(): void
    {
        $this->http->willReturnJson([
            'errors' => [['message' => 'Rate limited']],
            'data' => ['gateway_id' => 'abc123', 'secret' => 'window_hmac_secret'],
        ], 429, 'GET', 'gateway/window/v3/integration');

        try {
            $this->createApi()->gateway()->getPaymentWindowIntegrationSettings();
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
        }

        $this->assertStringNotContainsString('window_hmac_secret', $this->logger->dump());

        $context = $this->logger->recordsAtLevel(LogLevel::WARNING)[0]['context'];
        $this->assertSame(
            '{"errors":[{"message":"Rate limited"}],"data":{"gateway_id":"abc123","secret":"[redacted]"}}',
            $context['response_body']
        );
    }

    public function testDebugApiStillRecordsUnredactedRequestAndResponse(): void
    {
        // Redaction applies to the log only; the explicit debug accessors on OnPayAPI
        // keep exposing the raw wire data as before.
        $this->http->willReturnJson(['data' => []], 200, 'GET', 'ping');

        $api = $this->createApi();
        $api->ping();

        $this->assertSame($this->expectedAuthorizationHeader(), $api->getLastHttpRequest()->getHeaders()['Authorization']);
        $this->assertSame('{"data":[]}', $api->getLastHttpResponse()->getBody());
    }

    private function expiredTokenStorage(): TokenStorageInterface
    {
        $token = json_encode([
            'provider_id' => self::BASE_AUTHORIZE_URI . '/oauth2/authorize|' . self::CLIENT_ID,
            'issued_at' => date('Y-m-d H:i:s', time() - 7200),
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'full',
            'access_token' => self::ACCESS_TOKEN,
            'refresh_token' => 'test_refresh_token',
        ]);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        return $tokenStorage;
    }
}
