<?php

namespace OnPay\API\Util;


class Link
{
    public function __construct(string $link)
    {
        $this->rel = null;
        $this->uri = $link ?? null;
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
