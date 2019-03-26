<?php

namespace OnPay\API\Gateway;


class Information
{
    /**
     * @internal Shall not be used outside the library
     * SimpleTransaction constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->gatewayId = (isset($data['gateway_id'])) ? $data['gateway_id'] : null;
    }

    /**
     * @var string|null
     */
    public $gatewayId;
}
