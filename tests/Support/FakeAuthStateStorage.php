<?php

namespace Tests\Support;

use OnPay\AuthStateStorageInterface;

/**
 * A minimal in-memory OnPay\AuthStateStorageInterface for driving the OAuth
 * state/PKCE flow in tests. Records whether clear() was called so tests can assert
 * the stored values are discarded once the flow completes.
 */
class FakeAuthStateStorage implements AuthStateStorageInterface
{
    private ?string $state;

    private ?string $codeVerifier;

    private bool $cleared = false;

    public function __construct(?string $state = null, ?string $codeVerifier = null)
    {
        $this->state = $state;
        $this->codeVerifier = $codeVerifier;
    }

    public function saveState(string $state): void
    {
        $this->state = $state;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function saveCodeVerifier(string $codeVerifier): void
    {
        $this->codeVerifier = $codeVerifier;
    }

    public function getCodeVerifier(): ?string
    {
        return $this->codeVerifier;
    }

    public function clear(): void
    {
        $this->state = null;
        $this->codeVerifier = null;
        $this->cleared = true;
    }

    public function wasCleared(): bool
    {
        return $this->cleared;
    }
}
