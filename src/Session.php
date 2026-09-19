<?php

namespace OnPay;


use OnPay\OAuth\Client\SessionInterface;

class Session implements SessionInterface {
    /**
     * @var array<array-key, mixed>
     */
    protected $values = [];

    /**
     * Ignores $key and returns the whole bag; OAuthClient only reads back what it just wrote.
     *
     * @param string $key
     * @return array<array-key, mixed>
     */
    public function take($key){
        return $this->values;
    }

    public function set($key, $value) {
        if ('state' === $key) {
            $this->values['state'] = \crypt((string) $value, 'state');
        } else {
            $this->values[$key] = $value;
        }
    }
}
