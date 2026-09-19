<?php

namespace OnPay\API\Gateway;

use OnPay\API\Util\DataReader;

class Information
{
    /**
     * @internal Shall not be used outside the library
     * SimpleTransaction constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->gatewayId = DataReader::stringOrNull($data, 'gateway_id');
    }

    public ?string $gatewayId = null;
}
