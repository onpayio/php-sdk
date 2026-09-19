<?php

namespace OnPay\OAuth\Client\Http;

use OnPay\OAuth\Client\Http\Exception\CurlException;

class CurlHttpClient implements HttpClientInterface
{
    /** @var \CurlHandle */
    private $curlChannel;

    /** @var bool */
    private $allowHttp = false;

    /** @var array<string,string> */
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

        return $this->exec($curlOptions, $request->getHeaders());
    }

    /**
     * @return void
     */
    private function curlInit()
    {
        $curlChannel = \curl_init();
        // @codeCoverageIgnoreStart
        if (false === $curlChannel) {
            throw new CurlException('unable to create cURL channel');
        }
        // @codeCoverageIgnoreEnd
        $this->curlChannel = $curlChannel;
    }

    /**
     * @return void
     */
    private function curlReset()
    {
        \curl_reset($this->curlChannel);
        $this->responseHeaderList = [];
    }

    /**
     * @param array<string,string> $requestHeaders
     *
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
            CURLOPT_TIMEOUT => 30,
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

        // @codeCoverageIgnoreStart
        if (false === \curl_setopt_array($this->curlChannel, $curlOptions + $defaultCurlOptions)) {
            throw new CurlException('unable to set cURL options');
        }
        // @codeCoverageIgnoreEnd

        $responseData = \curl_exec($this->curlChannel);
        if (false === \is_string($responseData)) {
            // curl_exec returns true/false when CURLOPT_RETURNTRANSFER is not
            // set, but false|string when CURLOPT_RETURNTRANSFER _IS_ set, but
            // Psalm is not clever enough to distinguish this, so if the
            // response is NOT a string it MUST be false
            throw new CurlException(\sprintf('[%d] %s', \curl_errno($this->curlChannel), \curl_error($this->curlChannel)));
        }

        // @codeCoverageIgnoreStart
        return new Response(
            (int) \curl_getinfo($this->curlChannel, CURLINFO_HTTP_CODE),
            $responseData,
            $this->responseHeaderList
        );
        // @codeCoverageIgnoreEnd
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
        $headerParts = \explode(':', $headerData, 2);
        if (2 === \count($headerParts)) {
            $this->responseHeaderList[\trim($headerParts[0])] = \trim($headerParts[1]);
        }

        return self::safeStrlen($headerData);
    }

    public static function safeStrlen(string $str): int
    {
        if (\function_exists('mb_strlen')) {
            // mb_strlen in PHP 7.x can return false.
            /** @psalm-suppress RedundantCast */
            return (int) \mb_strlen($str, '8bit');
        } else {
            // @codeCoverageIgnoreStart
            return \strlen($str);
            // @codeCoverageIgnoreEnd
        }
    }
}
