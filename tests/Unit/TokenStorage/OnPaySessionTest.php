<?php

namespace Tests\Unit\TokenStorage;

use OnPay\Session;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for OnPay\Session (an in-memory SessionInterface, not backed by $_SESSION).
 */
class OnPaySessionTest extends TestCase
{
    public function testSetStoresValueUnderKey(): void
    {
        $session = new Session();
        $session->set('foo', 'bar');

        // PINNED CURRENT BEHAVIOUR: take() ignores its $key argument entirely and returns the
        // whole internal $values array — despite SessionInterface documenting "Get value,
        // delete key". Reported to caller. We assert the actual (whole-array) return value.
        self::assertSame(['foo' => 'bar'], $session->take('foo'));
    }

    public function testSetStateCryptsTheValue(): void
    {
        $session = new Session();
        $session->set('state', 'my-state');

        $stored = $session->take('state');
        self::assertArrayHasKey('state', $stored);
        // 'state' is stored crypt()-ed with the salt 'state', not verbatim
        self::assertSame(\crypt('my-state', 'state'), $stored['state']);
        self::assertNotSame('my-state', $stored['state']);
    }

    public function testTakeIgnoresKeyAndDoesNotDelete(): void
    {
        $session = new Session();
        $session->set('a', '1');
        $session->set('b', '2');

        // take() returns the whole map regardless of the key requested...
        self::assertSame(['a' => '1', 'b' => '2'], $session->take('a'));
        // ...and does NOT remove anything (contrary to the interface contract).
        self::assertSame(['a' => '1', 'b' => '2'], $session->take('nonexistent'));
    }
}
