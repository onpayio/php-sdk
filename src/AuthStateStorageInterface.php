<?php

declare(strict_types=1);

namespace OnPay;

/**
 * Stores the short-lived OAuth authorization values that must survive the redirect
 * between {@see OnPayAPI::authorize()} and {@see OnPayAPI::finishAuthorize()}: the
 * CSRF `state` and the PKCE `code_verifier`.
 *
 * Both values are generated while building the authorize URL and are needed again
 * when the authorization code comes back — typically in a *different* request, and,
 * when the SDK is embedded in a plugin installed across many instances, potentially
 * in a different process altogether. Each instance therefore supplies its own
 * storage (a session, a database row, a cache entry keyed to the visitor).
 *
 * - `state` lets `finishAuthorize()` reject an authorization code that was not
 *   initiated by this visitor (authorization-code injection / account linking).
 * - `code_verifier` lets `finishAuthorize()` complete the PKCE exchange.
 *
 * Implementations should scope the stored values to the current visitor and treat
 * them as single-use; `clear()` is called once the flow completes (success or
 * failure).
 */
interface AuthStateStorageInterface {
    /**
     * Persist the CSRF `state` generated for this authorization attempt.
     *
     * @param string $state
     * @return void
     */
    public function saveState(string $state): void;

    /**
     * Return the previously stored CSRF `state`, or null if none is stored.
     *
     * @return null|string
     */
    public function getState(): ?string;

    /**
     * Persist the PKCE `code_verifier` generated for this authorization attempt.
     *
     * @param string $codeVerifier
     * @return void
     */
    public function saveCodeVerifier(string $codeVerifier): void;

    /**
     * Return the previously stored PKCE `code_verifier`, or null if none is stored.
     *
     * @return null|string
     */
    public function getCodeVerifier(): ?string;

    /**
     * Discard the stored `state` and `code_verifier`. Called once the authorization
     * flow completes, whether it succeeded or failed, so nothing lingers for reuse.
     *
     * @return void
     */
    public function clear(): void;
}
