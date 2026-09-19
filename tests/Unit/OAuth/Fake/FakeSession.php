<?php

namespace Tests\Unit\OAuth\Fake;

use OnPay\OAuth\Client\SessionInterface;

/**
 * Faithful in-memory SessionInterface: a proper per-key store whose take()
 * returns and removes the value for the requested key.
 *
 * This lets OAuthClient's real state/PKCE handling exercise itself in
 * isolation (unlike OnPay\Session, whose take() ignores the key).
 */
class FakeSession implements SessionInterface
{
    /** @var array<string,mixed> */
    public array $values = [];

    public function set($key, $value): void
    {
        $this->values[$key] = $value;
    }

    public function take($key): mixed
    {
        $value = $this->values[$key] ?? null;
        unset($this->values[$key]);

        return $value;
    }
}
