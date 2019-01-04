<?php

namespace OnPay\API\Util;


class Link
{
    public function __construct($link = null)
    {
        $this->rel = null;
        $this->uri = $link;
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
