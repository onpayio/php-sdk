<?php

namespace OnPay\API\Util;


class Link
{
    public function __construct($rel = null, $link = null)
    {
        $this->rel = $rel;
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
