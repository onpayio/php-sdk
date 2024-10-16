<?php

namespace OnPay\OAuth\Client;

interface SessionInterface {
    /**
     * Get value, delete key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function take($key);

    /**
     * Set key to value.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function set($key, $value);
}
