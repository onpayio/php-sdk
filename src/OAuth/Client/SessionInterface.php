<?php

declare(strict_types=1);

namespace OnPay\OAuth\Client;

interface SessionInterface {
    /**
     * Get value, delete key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function take(string $key): mixed;

    /**
     * Set key to value.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function set(string $key, mixed $value): void;
}
