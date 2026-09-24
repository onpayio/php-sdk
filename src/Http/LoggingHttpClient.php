<?php

declare(strict_types=1);

namespace OnPay\Http;

use OnPay\Log\Redactor;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Decorates the transport so every API and OAuth round trip is reported to a
 * PSR-3 logger.
 *
 * Successful exchanges are logged at debug level with method, URI and status
 * only. Non-2xx responses (warning for 4xx, error for 5xx) and transport
 * failures (error) additionally carry the request and response, passed through
 * {@see Redactor} so bearer tokens, OAuth material and cardholder data never
 * reach the log.
 *
 * @internal Shall not be used outside the library.
 */
final class LoggingHttpClient implements RecordingHttpClientInterface {
    private RecordingHttpClientInterface $inner;

    private LoggerInterface $logger;

    private Redactor $redactor;

    public function __construct(RecordingHttpClientInterface $inner, LoggerInterface $logger, ?Redactor $redactor = null) {
        $this->inner = $inner;
        $this->logger = $logger;
        $this->redactor = $redactor ?? new Redactor();
    }

    public function sendRequest(RequestInterface $request): ResponseInterface {
        $method = $request->getMethod();
        $uri = (string) $request->getUri();

        try {
            $response = $this->inner->sendRequest($request);
        } catch (\Throwable $e) {
            $this->logger->error('OnPay {method} {uri} failed: {reason}', [
                'method' => $method,
                'uri' => $uri,
                'reason' => $e->getMessage(),
                'request_headers' => $this->redactor->redactHeaders(MessageUtil::flattenHeaders($request)),
                'request_body' => $this->redactor->redactBody(MessageUtil::bodyOrNull($request), MessageUtil::flattenHeaders($request)),
                'exception' => $e,
            ]);

            throw $e;
        }

        $status = $response->getStatusCode();
        if ($status >= 200 && $status < 300) {
            $this->logger->debug('OnPay {method} {uri} responded HTTP {status}', [
                'method' => $method,
                'uri' => $uri,
                'status' => $status,
            ]);

            return $response;
        }

        $this->logger->log(
            $status >= 500 ? LogLevel::ERROR : LogLevel::WARNING,
            'OnPay {method} {uri} responded HTTP {status}',
            [
                'method' => $method,
                'uri' => $uri,
                'status' => $status,
                'request_headers' => $this->redactor->redactHeaders(MessageUtil::flattenHeaders($request)),
                'request_body' => $this->redactor->redactBody(MessageUtil::bodyOrNull($request), MessageUtil::flattenHeaders($request)),
                'response_headers' => $this->redactor->redactHeaders(MessageUtil::flattenHeaders($response)),
                'response_body' => $this->redactor->redactBody(MessageUtil::bodyOrNull($response), MessageUtil::flattenHeaders($response)),
            ]
        );

        return $response;
    }

    public function getLastRequest(): ?RequestInterface {
        return $this->inner->getLastRequest();
    }

    public function getLastResponse(): ?ResponseInterface {
        return $this->inner->getLastResponse();
    }
}
