<?php

namespace OnPay\API\Gateway;

use OnPay\API\Util\DataReader;

class PaymentWindowIntegrationSettings
{
    /**
     * @internal Shall not be used outside the library
     * SimpleTransaction constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->secret = DataReader::requireString($data, 'secret');
    }

    public string $secret;
}
