<?php

declare(strict_types=1);

namespace OnPay\Http;

use OnPay\Log\Redactor;
use OnPay\OAuth\Client\Http\Request;
use OnPay\OAuth\Client\Http\Response;
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
class LoggingHttpClient implements RecordingHttpClientInterface {
    private RecordingHttpClientInterface $inner;

    private LoggerInterface $logger;

    private Redactor $redactor;

    public function __construct(RecordingHttpClientInterface $inner, LoggerInterface $logger, ?Redactor $redactor = null) {
        $this->inner = $inner;
        $this->logger = $logger;
        $this->redactor = $redactor ?? new Redactor();
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function send(Request $request): Response {
        try {
            $response = $this->inner->send($request);
        } catch (\Throwable $e) {
            $this->logger->error('OnPay {method} {uri} failed: {reason}', [
                'method' => $request->getMethod(),
                'uri' => $request->getUri(),
                'reason' => $e->getMessage(),
                'request_headers' => $this->redactor->redactHeaders($request->getHeaders()),
                'request_body' => $this->redactor->redactBody($request->getBody(), $request->getHeaders()),
                'exception' => $e,
            ]);

            throw $e;
        }

        if ($response->isOkay()) {
            $this->logger->debug('OnPay {method} {uri} responded HTTP {status}', [
                'method' => $request->getMethod(),
                'uri' => $request->getUri(),
                'status' => $response->getStatusCode(),
            ]);

            return $response;
        }

        $this->logger->log(
            $response->getStatusCode() >= 500 ? LogLevel::ERROR : LogLevel::WARNING,
            'OnPay {method} {uri} responded HTTP {status}',
            [
                'method' => $request->getMethod(),
                'uri' => $request->getUri(),
                'status' => $response->getStatusCode(),
                'request_headers' => $this->redactor->redactHeaders($request->getHeaders()),
                'request_body' => $this->redactor->redactBody($request->getBody(), $request->getHeaders()),
                'response_headers' => $this->redactor->redactHeaders($response->getHeaders()),
                'response_body' => $this->redactor->redactBody($response->getBody(), $response->getHeaders()),
            ]
        );

        return $response;
    }

    /**
     * @return Request|null
     */
    public function getLastRequest(): ?Request {
        return $this->inner->getLastRequest();
    }

    /**
     * @return Response|null
     */
    public function getLastResponse(): ?Response {
        return $this->inner->getLastResponse();
    }

    public function getInnerClient(): RecordingHttpClientInterface {
        return $this->inner;
    }
}
