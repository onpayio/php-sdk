<?php

declare(strict_types=1);

namespace OnPay\Auth;

use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Grant\RefreshToken;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use OnPay\API\Exception\ConnectionException;
use OnPay\API\Exception\TokenException;
use OnPay\AuthStateStorageInterface;
use OnPay\InternalTokenStorage;
use OnPay\OAuth\OnPayProvider;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Owns the OAuth authorize flow and the access-token lifecycle (obtaining, refreshing
 * and storing tokens). {@see \OnPay\Http\ApiClient} depends on this for authenticated
 * requests; the facade {@see \OnPay\OnPayAPI} delegates its authorize()/finishAuthorize()
 * to it. The `OnPayProvider` and token storage are fully encapsulated here.
 *
 * @internal
 */
class TokenManager {
    private OnPayProvider $oauth2Provider;

    private InternalTokenStorage $tokenStorage;

    private ?AuthStateStorageInterface $authStateStorage;

    private string $scope;

    public function __construct(
        OnPayProvider $oauth2Provider,
        InternalTokenStorage $tokenStorage,
        ?AuthStateStorageInterface $authStateStorage,
        string $scope
    ) {
        $this->oauth2Provider = $oauth2Provider;
        $this->tokenStorage = $tokenStorage;
        $this->authStateStorage = $authStateStorage;
        $this->scope = $scope;
    }

    /**
     * Returns a URL the user should be redirected to, for authorizing.
     *
     * When an {@see AuthStateStorageInterface} was supplied, the generated CSRF `state`
     * and PKCE `code_verifier` are stored so {@see finishAuthorize()} can verify the
     * callback and complete the PKCE exchange. Without it, neither protection is active
     * and the OAuth flow is deprecated.
     */
    public function authorize(): string {
        $url = $this->oauth2Provider->getAuthorizationUrl(['scope' => $this->scope]);

        if (null === $this->authStateStorage) {
            @\trigger_error(
                'Calling authorize() without an AuthStateStorageInterface is deprecated: '
                . 'CSRF state verification and PKCE are disabled. Pass one to the OnPayAPI constructor.',
                \E_USER_DEPRECATED
            );

            return $url;
        }

        $this->authStateStorage->saveState($this->oauth2Provider->getState());
        $this->authStateStorage->saveCodeVerifier((string) $this->oauth2Provider->getPkceCode());

        return $url;
    }

    /**
     * Exchanges the authorization code for an access token and stores it.
     *
     * When an {@see AuthStateStorageInterface} was supplied, the `state` returned on the
     * callback MUST be passed as $returnedState; it is compared (timing-safe) against the
     * value stored by {@see authorize()} and a mismatch aborts with a {@see TokenException}
     * before any token exchange. The stored PKCE verifier is then used to complete the
     * exchange, and the storage is cleared afterwards. Without the storage, no state is
     * verified and the call is deprecated.
     *
     * @param string $code
     * @param string|null $returnedState the `state` query parameter from the callback
     * @throws TokenException on a state mismatch or a token endpoint failure
     * @throws ConnectionException on a transport failure
     */
    public function finishAuthorize(string $code, ?string $returnedState = null): void {
        if (null === $this->authStateStorage) {
            @\trigger_error(
                'Calling finishAuthorize() without an AuthStateStorageInterface is deprecated: '
                . 'the OAuth state (CSRF) is not verified. Pass one to the OnPayAPI constructor '
                . 'and pass the returned state as the second argument.',
                \E_USER_DEPRECATED
            );

            $this->exchangeAuthorizationCode($code);

            return;
        }

        $expectedState = (string) $this->authStateStorage->getState();
        if ('' === $expectedState || !\hash_equals($expectedState, (string) $returnedState)) {
            $this->authStateStorage->clear();
            throw new TokenException('OAuth state mismatch: the authorization response could not be verified (possible CSRF)');
        }

        $this->oauth2Provider->setPkceCode((string) $this->authStateStorage->getCodeVerifier());

        try {
            $this->exchangeAuthorizationCode($code);
        } finally {
            $this->authStateStorage->clear();
        }
    }

    /**
     * @throws TokenException
     * @throws ConnectionException
     */
    private function exchangeAuthorizationCode(string $code): void {
        try {
            $token = $this->requestAccessToken('authorization_code', ['code' => $code], 'unable to obtain access_token');
        } catch (ClientExceptionInterface $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode(), $e);
        }

        $this->tokenStorage->storeAccessToken($token);
    }

    /**
     * Builds an authenticated request for an API call, refreshing the stored token first
     * when it has expired.
     *
     * @param array<string,mixed> $options headers/body for the request
     * @throws TokenException when there is no usable token
     * @throws ClientExceptionInterface on a transport failure while refreshing
     */
    public function authenticateRequest(string $method, string $absoluteUrl, array $options): RequestInterface {
        $token = $this->getValidAccessToken();
        if (null === $token) {
            throw new TokenException('Invalid response. Possible invalid token.');
        }

        return $this->oauth2Provider->getAuthenticatedRequest($method, $absoluteUrl, $token, $options);
    }

    /**
     * Returns the stored token, refreshed if it has expired, or null when there is
     * no usable token (nothing stored, or expired without a refresh token).
     *
     * @throws TokenException
     * @throws ClientExceptionInterface
     */
    private function getValidAccessToken(): ?AccessTokenInterface {
        $token = $this->tokenStorage->getAccessToken();
        if (null === $token || !self::hasExpired($token)) {
            return $token;
        }

        $refreshToken = $token->getRefreshToken();
        if (null === $refreshToken) {
            return null;
        }

        $refreshed = $this->requestAccessToken(
            new RefreshToken(),
            ['refresh_token' => $refreshToken, 'scope' => $this->scope],
            'unable to refresh access_token'
        );
        // OnPay may omit the refresh token from a refresh response; keep the current one then.
        $refreshed = new AccessToken($refreshed->jsonSerialize() + ['refresh_token' => $refreshToken]);
        $this->tokenStorage->storeAccessToken($refreshed);

        return $refreshed;
    }

    /**
     * Calls the token endpoint, translating league's failures into the SDK's
     * TokenException. Transport failures (ClientExceptionInterface) pass through.
     *
     * @param string|AbstractGrant $grant
     * @param array<string,mixed> $options
     * @throws TokenException
     * @throws ClientExceptionInterface
     */
    private function requestAccessToken($grant, array $options, string $failure): AccessTokenInterface {
        try {
            return $this->oauth2Provider->getAccessToken($grant, $options);
        } catch (IdentityProviderException | \UnexpectedValueException $e) {
            throw new TokenException($failure . ': ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Tokens without an expiry (static API tokens) never expire. League stores
     * `expires_in: 0` as 0 and refuses to evaluate it, hence the emptiness check.
     */
    private static function hasExpired(AccessTokenInterface $token): bool {
        $expires = $token->getExpires();

        return null !== $expires && 0 !== $expires && $token->hasExpired();
    }
}
