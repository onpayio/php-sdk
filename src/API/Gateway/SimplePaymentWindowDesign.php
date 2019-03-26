<?php

namespace OnPay\API\Gateway;


class SimplePaymentWindowDesign
{
    /**
     * @internal Shall not be used outside the library
     * SimpleTransaction constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->name = (isset($data['name'])) ? $data['name'] : null;
    }

    /**
     * @var string|null
     */
    public $name;
}
