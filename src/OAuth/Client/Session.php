<?php

namespace OnPay\OAuth\Client;

use OnPay\OAuth\Client\Exception\SessionException;

class Session implements SessionInterface
{
    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function set($key, $value)
    {
        self::requireSession();
        $_SESSION[$key] = $value;
    }

    /**
     * Get value, delete key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function take($key)
    {
        self::requireSession();
        if (false === \array_key_exists($key, $_SESSION)) {
            throw new SessionException(\sprintf('key "%s" not found in session', $key));
        }
        $value = $_SESSION[$key];
        unset($_SESSION[$key]);

        return $value;
    }

    /**
     * @return void
     */
    private static function requireSession()
    {
        if (PHP_SESSION_ACTIVE !== \session_status()) {
            // if we have no active session, bail, we expect an active session
            // and will NOT fiddle with the application's existing session
            // management
            throw new SessionException('no active session');
        }
    }
}
