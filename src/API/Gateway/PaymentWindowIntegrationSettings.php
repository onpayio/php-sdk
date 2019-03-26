<?php

namespace OnPay\API\Gateway;


class PaymentWindowIntegrationSettings
{
    /**
     * @internal Shall not be used outside the library
     * SimpleTransaction constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->secret = (isset($data['secret'])) ? $data['secret'] : '';
    }

    /**
     * @var string
     */
    public $secret;
}
