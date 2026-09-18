<?php

namespace Tests\Unit\TokenStorage;

use PHPUnit\Framework\TestCase;

/**
 * Base for tests that exercise classes gated by session_status().
 *
 * These SDK classes (OnPay\OAuth\Client\Session, OnPay\OAuth\Client\SessionTokenStorage)
 * refuse to operate unless a real PHP session is active (they call session_status()), so a
 * genuine session is required — writing $_SESSION alone does NOT make session_status()
 * return PHP_SESSION_ACTIVE. We start a cookie-less, cache-limiter-less session pointed at a
 * writable save_path so session_start() is warning-clean under PHPUnit's failOnWarning, and
 * we always close it in tearDown so the suite stays order-independent (a leaked active
 * session would leak into other test files run by the same process).
 */
abstract class SessionTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureNoSession();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $this->ensureNoSession();
        $_SESSION = [];
        parent::tearDown();
    }

    /**
     * Start a real, header-free session and reset its contents for isolation.
     */
    protected function startSession(): void
    {
        if (PHP_SESSION_ACTIVE !== session_status()) {
            $savePath = getenv('TMPDIR');
            ini_set('session.save_path', false !== $savePath && '' !== $savePath ? $savePath : sys_get_temp_dir());
            ini_set('session.use_cookies', '0');
            ini_set('session.cache_limiter', '');
            self::assertTrue(session_start(), 'expected session_start() to succeed');
        }
        $_SESSION = [];
    }

    private function ensureNoSession(): void
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            $_SESSION = [];
            session_write_close();
        }
    }
}
