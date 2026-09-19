<?php

declare(strict_types=1);

namespace OnPay;

use OnPay\Http\RecordingHttpClientInterface;
use OnPay\OAuth\Client\Http\CurlHttpClient;
use OnPay\OAuth\Client\Http\Request;
use OnPay\OAuth\Client\Http\Response;

class CurlHttpClientLogger extends CurlHttpClient implements RecordingHttpClientInterface {
    /**
     * @var Request|null
     */
    protected ?Request $lastRequest = null;

    /**
     * @var Response|null
     */
    protected ?Response $lastResponse = null;

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function send(Request $request): Response {
        $this->lastRequest = $request;
        $response = parent::send($request);
        // @codeCoverageIgnoreStart
        $this->lastResponse = $response;
        return $response;
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return Request|null
     */
    public function getLastRequest(): ?Request {
        return $this->lastRequest;
    }

    /**
     * @return Response|null
     */
    public function getLastResponse(): ?Response {
        return $this->lastResponse;
    }
}
