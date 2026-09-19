<?php

namespace Tests\Unit\TokenStorage;

use OnPay\OAuth\Client\Exception\SessionException;
use OnPay\OAuth\Client\Session;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Coverage for OnPay\OAuth\Client\Session (the session-backed SessionInterface).
 *
 * Runs each test in a separate process so the real PHP session can be started cleanly (see
 * SessionTokenStorageTest for the rationale — ini_set on session.* fails once output is sent).
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class OAuthClientSessionTest extends SessionTestCase
{
    public function testSetThrowsWithoutActiveSession(): void
    {
        $session = new Session();

        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('no active session');
        $session->set('foo', 'bar');
    }

    public function testTakeThrowsWithoutActiveSession(): void
    {
        $session = new Session();

        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('no active session');
        $session->take('foo');
    }

    public function testSetThenTakeReturnsValueAndRemovesKey(): void
    {
        $this->startSession();
        $session = new Session();

        $session->set('foo', 'bar');
        self::assertSame('bar', $_SESSION['foo']);

        self::assertSame('bar', $session->take('foo'));
        // take() deletes the key after reading it
        self::assertArrayNotHasKey('foo', $_SESSION);
    }

    public function testTakeThrowsWhenKeyMissing(): void
    {
        $this->startSession();
        $session = new Session();

        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('key "missing" not found in session');
        $session->take('missing');
    }
}
