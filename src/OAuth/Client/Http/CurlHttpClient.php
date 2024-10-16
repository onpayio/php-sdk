<?php

namespace OnPay\OAuth\Client\Http;

use OnPay\OAuth\Client\Http\Exception\CurlException;

class CurlHttpClient implements HttpClientInterface
{
    /** @var resource */
    private $curlChannel;

    /** @var bool */
    private $allowHttp = false;

    /** @var array */
    private $responseHeaderList = [];

    public function __construct(array $configData = [])
    {
        if (\array_key_exists('allowHttp', $configData)) {
            $this->allowHttp = (bool) $configData['allowHttp'];
        }
        $this->curlInit();
    }

    public function __destruct()
    {
        \curl_close($this->curlChannel);
    }

    /**
     * @return Response
     */
    public function send(Request $request)
    {
        $curlOptions = [
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_URL => $request->getUri(),
        ];

        if (\in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'], true)) {
            $curlOptions[CURLOPT_POSTFIELDS] = $request->getBody();
        }

        $response = $this->exec($curlOptions, $request->getHeaders());
        if (!$response->isOkay()) {
            \error_log(\sprintf('REQUEST=%s, RESPONSE=%s', (string) $request, (string) $response));
        }

        return $response;
    }

    /**
     * @return void
     */
    private function curlInit()
    {
        $curlChannel = \curl_init();
        if (false === $curlChannel) {
            throw new CurlException('unable to create cURL channel');
        }
        $this->curlChannel = $curlChannel;
    }

    /**
     * @return void
     */
    private function curlReset()
    {
        if (\function_exists('curl_reset')) {
            \curl_reset($this->curlChannel);
        } else {
            \curl_close($this->curlChannel);
            $this->curlInit();
        }
        $this->responseHeaderList = [];
    }

    /**
     * @return Response
     */
    private function exec(array $curlOptions, array $requestHeaders)
    {
        // make sure we always start with a clean slate, we do this here
        // and not after curl_exec because when calling CurlHttpClient::exec
        // again after a caught exception may result in unexpected weirdness
        $this->curlReset();

        $defaultCurlOptions = [
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => $this->allowHttp ? CURLPROTO_HTTPS | CURLPROTO_HTTP : CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYHOST => 2,    // default, but just to make sure
            CURLOPT_SSL_VERIFYPEER => true, // default, but just to make sure
            CURLOPT_HEADERFUNCTION => [$this, 'responseHeaderFunction'],
        ];

        if (0 !== \count($requestHeaders)) {
            $curlRequestHeaders = [];
            foreach ($requestHeaders as $k => $v) {
                $curlRequestHeaders[] = \sprintf('%s: %s', $k, $v);
            }
            $defaultCurlOptions[CURLOPT_HTTPHEADER] = $curlRequestHeaders;
        }

        if (false === \curl_setopt_array($this->curlChannel, $curlOptions + $defaultCurlOptions)) {
            throw new CurlException('unable to set cURL options');
        }

        $responseData = \curl_exec($this->curlChannel);
        if (false === \is_string($responseData)) {
            // curl_exec returns true/false when CURLOPT_RETURNTRANSFER is not
            // set, but false|string when CURLOPT_RETURNTRANSFER _IS_ set, but
            // Psalm is not clever enough to distinguish this, so if the
            // response is NOT a string it MUST be false
            throw new CurlException(\sprintf('[%d] %s', \curl_errno($this->curlChannel), \curl_error($this->curlChannel)));
        }

        return new Response(
            \curl_getinfo($this->curlChannel, CURLINFO_HTTP_CODE),
            $responseData,
            $this->responseHeaderList
        );
    }

    /**
     * @param resource $curlChannel
     * @param string   $headerData
     *
     * @return int
     */
    private function responseHeaderFunction($curlChannel, $headerData)
    {
        // we do NOT support multiple response headers with the same key, the
        // later one(s) will overwrite the earlier one
        if (false !== \strpos($headerData, ':')) {
            list($key, $value) = \explode(':', $headerData, 2);
            $this->responseHeaderList[\trim($key)] = \trim($value);
        }

        return self::safeStrlen($headerData);
    }

    public static function safeStrlen(string $str)
    {
        if (\function_exists('mb_strlen')) {
            // mb_strlen in PHP 7.x can return false.
            /** @psalm-suppress RedundantCast */
            return (int) \mb_strlen($str, '8bit');
        } else {
            return \strlen($str);
        }
    }
}
