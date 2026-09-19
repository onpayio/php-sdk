<?php

declare(strict_types=1);

namespace OnPay\API\Util;

class Link
{
    public function __construct(?string $rel = null, ?string $link = null)
    {
        $this->rel = $rel;
        $this->uri = $link;
    }

    public ?string $rel = null;

    public ?string $uri = null;
}
