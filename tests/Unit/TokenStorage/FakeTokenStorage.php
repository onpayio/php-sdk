<?php

namespace Tests\Unit\TokenStorage;

use OnPay\TokenStorageInterface;

/**
 * A minimal stateful OnPay\TokenStorageInterface used to drive InternalTokenStorage.
 *
 * Owned by this test package (Tests\Unit\TokenStorage) so it stays stable regardless of
 * fakes maintained under tests/Unit/OAuth/Fake. It remembers the last saved token, which is
 * what InternalTokenStorage::convertToken() relies on when it re-reads after saving.
 */
class FakeTokenStorage implements TokenStorageInterface
{
    /** @var string|null */
    private $token;

    public function __construct(?string $token = null)
    {
        $this->token = $token;
    }

    /**
     * @return string|null
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return void
     */
    public function saveToken($token)
    {
        $this->token = $token;
    }
}
