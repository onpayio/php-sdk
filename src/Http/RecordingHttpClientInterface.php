<?php

namespace OnPay\Http;

use OnPay\OAuth\Client\Http\HttpClientInterface;
use OnPay\OAuth\Client\Http\Request;
use OnPay\OAuth\Client\Http\Response;

/**
 * An HTTP client that records the last request/response it handled.
 *
 * OnPayAPI reads these to power the public getLastHttpRequest()/getLastHttpResponse()
 * debug API, so any client plugged into OnPayAPI must expose them. Both the bundled
 * cURL client (CurlHttpClientLogger) and the PSR-18 adapter (Psr18HttpClient)
 * implement this.
 */
interface RecordingHttpClientInterface extends HttpClientInterface {
    /**
     * @return Request|null
     */
    public function getLastRequest();

    /**
     * @return Response|null
     */
    public function getLastResponse();
}
