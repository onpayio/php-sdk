<?php

namespace OnPay\API\Util;


class Link
{
    public function __construct(array $data)
    {
        $this->rel = $data['rel'] ?? null;
        $this->uri = $data['uri'] ?? null;
    }

    /**
     * @var string
     */
    public $rel;

    /**
     * @var string
     */
    public $uri;
}
