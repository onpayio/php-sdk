<?php

namespace OnPay;


use fkooman\OAuth\Client\SessionInterface;

class Session implements SessionInterface {
    protected $values;

    public function take($key){
        return $this->values;
    }

    public function set($key, $value) {
        if ('state' === $key) {
            $this->values['state'] = \crypt($value, 'state');
        } else {
            $this->values[$key] = $value;
        }
    }
}
