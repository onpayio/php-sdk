<?php

namespace Tests\Unit\OAuth\Fake;

use OnPay\TokenStorageInterface;

/**
 * In-memory OnPay TokenStorageInterface used to back InternalTokenStorage.
 *
 * Returns a preconfigured token JSON and records every saved token.
 */
class FakeOnPayTokenStorage implements TokenStorageInterface
{
    /** @var string[] */
    public array $saved = [];

    private ?string $token;

    public function __construct(?string $token = null)
    {
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function saveToken($token)
    {
        $this->saved[] = $token;
        $this->token = $token;

        return null;
    }

    public function getLastSaved(): ?string
    {
        if (0 === \count($this->saved)) {
            return null;
        }

        return $this->saved[\count($this->saved) - 1];
    }
}
