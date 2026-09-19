<?php

namespace OnPay\API\Gateway;

use OnPay\API\Util\DataReader;

class SimplePaymentWindowDesign
{
    /**
     * @internal Shall not be used outside the library
     * SimpleTransaction constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->name = DataReader::stringOrNull($data, 'name');
    }

    public ?string $name = null;
}
