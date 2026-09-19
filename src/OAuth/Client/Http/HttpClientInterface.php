<?php

declare(strict_types=1);

namespace OnPay\OAuth\Client\Http;

interface HttpClientInterface
{
    /**
     * @return Response
     */
    public function send(Request $request): Response;
}
