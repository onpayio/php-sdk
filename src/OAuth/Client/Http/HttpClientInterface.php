<?php

namespace OnPay\OAuth\Client\Http;

interface HttpClientInterface
{
    /**
     * @return Response
     */
    public function send(Request $request);
}
