<?php

declare(strict_types=1);

namespace OnPay;


use OnPay\OAuth\Client\SessionInterface;

class Session implements SessionInterface {
    /**
     * @var array<array-key, mixed>
     */
    protected array $values = [];

    /**
     * Ignores $key and returns the whole bag; OAuthClient only reads back what it just wrote.
     *
     * @param string $key
     * @return array<array-key, mixed>
     */
    public function take(string $key): array {
        return $this->values;
    }

    public function set(string $key, mixed $value): void {
        if ('state' === $key) {
            $this->values['state'] = \crypt((string) $value, 'state');
        } else {
            $this->values[$key] = $value;
        }
    }
}
