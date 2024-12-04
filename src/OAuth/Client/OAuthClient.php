<?php

namespace OnPay\OAuth\Client;

use OnPay\OAuth\Client\Http\HttpClientInterface;
use OnPay\OAuth\Client\Session;
use OnPay\OAuth\Client\SessionInterface;
use OnPay\OAuth\Client\Exception\AuthorizeException;
use OnPay\OAuth\Client\Exception\OAuthException;
use OnPay\OAuth\Client\Exception\TokenException;
use OnPay\OAuth\Client\Http\Request;
use OnPay\InternalTokenStorage;
use OnPay\TokenStorageInterface;

class OAuthClient {
    protected $session;

    /** @var \DateTime */
    protected $dateTime;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var Http\HttpClientInterface */
    private $httpClient;

    public function __construct(InternalTokenStorage $tokenStorage, HttpClientInterface $httpClient) {
        $this->tokenStorage = $tokenStorage;
        $this->httpClient = $httpClient;
        $this->session = new Session();
        $this->dateTime = new \DateTime();
    }

    /**
     * @return void
     */
    public function setSession(SessionInterface $session) {
        $this->session = $session;
    }

    /**
     * Perform a GET request, convenience wrapper for ::send().
     *
     * @param string $userId
     * @param string $requestScope
     * @param string $requestUri
     *
     * @return false|Http\Response
     */
    public function get(Provider $provider, $userId, $requestScope, $requestUri, array $requestHeaders = [])
    {
        return $this->send($provider, $userId, $requestScope, Request::get($requestUri, $requestHeaders));
    }

    /**
     * Perform a POST request, convenience wrapper for ::send().
     *
     * @param string $userId
     * @param string $requestScope
     * @param string $requestUri
     *
     * @return false|Http\Response
     */
    public function post(Provider $provider, $userId, $requestScope, $requestUri, array $postBody, array $requestHeaders = [])
    {
        return $this->send($provider, $userId, $requestScope, Request::post($requestUri, $postBody, $requestHeaders));
    }

    public function send(Provider $provider, $userId, $requestScope, Request $request) {
        $accessToken = $this->getAccessToken($provider, $userId, $requestScope);
        if (false === $accessToken) {
            return false;
        }

        if ($accessToken->isExpired($this->dateTime)) {
            // access_token is expired, try to refresh it
            if (null === $accessToken->getRefreshToken()) {
                // we do not have a refresh_token, delete this access token, it
                // is useless now...
                $this->tokenStorage->deleteAccessToken($userId, $accessToken);

                return false;
            }

            // try to refresh the AccessToken
            $accessToken = $this->refreshAccessToken($provider, $userId, $accessToken);
            if (false === $accessToken) {
                // didn't work
                return false;
            }
        }

        // add Authorization header to the request headers
        $request->setHeader('Authorization', \sprintf('Bearer %s', $accessToken->getToken()));

        $response = $this->httpClient->send($request);
        if (401 === $response->getStatusCode()) {
            // the access_token was not accepted, but isn't expired, we assume
            // the user revoked it, also no need to try with refresh_token
            $this->tokenStorage->deleteAccessToken($userId, $accessToken);

            return false;
        }

        return $response;
    }

    /**
     * Obtain an authorization request URL to start the authorization process
     * at the OAuth provider.
     *
     * @param string $userId
     * @param string $scope       the space separated scope tokens
     * @param string $redirectUri the URL registered at the OAuth provider, to
     *                            be redirected back to
     *
     * @return string the authorization request URL
     *
     * @see https://tools.ietf.org/html/rfc6749#section-3.3
     * @see https://tools.ietf.org/html/rfc6749#section-3.1.2
     */
    public function getAuthorizeUri(Provider $provider, $userId, $scope, $redirectUri) {
        $codeVerifier = \str_replace('=', '', \base64_encode(\random_bytes(32)));
        $queryParameters = [
            'client_id' => $provider->getClientId(),
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
            'state' => \str_replace('=', '', \base64_encode(\random_bytes(32))),
            'response_type' => 'code',
            'code_challenge_method' => 'S256',
            'code_challenge' => \str_replace('=', '', \base64_encode(\hash('sha256', $codeVerifier, true))),
        ];

        $authorizeUri = \sprintf(
            '%s%s%s',
            $provider->getAuthorizationEndpoint(),
            false === \strpos($provider->getAuthorizationEndpoint(), '?') ? '?' : '&',
            \http_build_query($queryParameters, '&')
        );
        $this->session->set(
            '_oauth2_session',
            \array_merge(
                $queryParameters,
                [
                    'user_id' => $userId,
                    'provider_id' => $provider->getProviderId(),
                    'code_verifier' => $codeVerifier,
                ]
            )
        );

        return $authorizeUri;
    }

    /**
     * @param string $userId
     *
     * @return void
     */
    public function handleCallback(Provider $provider, $userId, array $getData) {
        if (\array_key_exists('error', $getData)) {
            // remove the session
            $this->session->take('_oauth2_session');

            throw new AuthorizeException($getData['error'], \array_key_exists('error_description', $getData) ? $getData['error_description'] : null);
        }

        if (false === \array_key_exists('code', $getData)) {
            throw new OAuthException('missing "code" query parameter from server response');
        }

        if (false === \array_key_exists('state', $getData)) {
            throw new OAuthException('missing "state" query parameter from server response');
        }

        $this->doHandleCallback($provider, $userId, $getData['code'], $getData['state']);
    }

    /**
     * @param string $userId
     * @param string $responseCode  the code passed to the "code" query parameter on the callback URL
     * @param string $responseState the state passed to the "state" query parameter on the callback URL
     *
     * @return void
     */
    private function doHandleCallback(Provider $provider, $userId, $responseCode, $responseState) {
        // get and delete the OAuth session information
        $sessionData = $this->session->take('_oauth2_session');

        if (false === \hash_equals($sessionData['state'], $responseState)) {
            // the OAuth state from the initial request MUST be the same as the
            // state used by the response
            throw new OAuthException('invalid session (state)');
        }

        // session providerId MUST match current set Provider
        if ($sessionData['provider_id'] !== $provider->getProviderId()) {
            throw new OAuthException('invalid session (provider_id)');
        }

        // session userId MUST match current set userId
        if ($sessionData['user_id'] !== $userId) {
            throw new OAuthException('invalid session (user_id)');
        }

        // prepare access_token request
        $tokenRequestData = [
            'client_id' => $provider->getClientId(),
            'grant_type' => 'authorization_code',
            'code' => $responseCode,
            'redirect_uri' => $sessionData['redirect_uri'],
            'code_verifier' => $sessionData['code_verifier'],
        ];

        $response = $this->httpClient->send(
            Request::post(
                $provider->getTokenEndpoint(),
                $tokenRequestData,
                self::getAuthorizationHeader(
                    $provider->getClientId(),
                    $provider->getSecret()
                )
            )
        );

        if (false === $response->isOkay()) {
            throw new TokenException('unable to obtain access_token', $response);
        }

        $this->tokenStorage->storeAccessToken(
            $userId,
            AccessToken::fromCodeResponse(
                $provider,
                $this->dateTime,
                $response->json(),
                // in case server does not return a scope, we know it granted
                // our requested scope (according to OAuth specification)
                $sessionData['scope']
            )
        );
    }

    /**
     * @param string $userId
     *
     * @return false|AccessToken
     */
    private function refreshAccessToken(Provider $provider, $userId, AccessToken $accessToken)
    {
        // prepare access_token request
        $tokenRequestData = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $accessToken->getRefreshToken(),
            'scope' => $accessToken->getScope(),
        ];

        $response = $this->httpClient->send(
            Request::post(
                $provider->getTokenEndpoint(),
                $tokenRequestData,
                self::getAuthorizationHeader(
                    $provider->getClientId(),
                    $provider->getSecret()
                )
            )
        );

        if (false === $response->isOkay()) {
            $responseData = $response->json();
            if (\array_key_exists('error', $responseData) && 'invalid_grant' === $responseData['error']) {
                // delete the access_token, we assume the user revoked it, that
                // is why we get "invalid_grant"
                $this->tokenStorage->deleteAccessToken($userId, $accessToken);

                return false;
            }

            throw new TokenException('unable to refresh access_token', $response);
        }

        // delete old AccessToken as we'll write a new one anyway...
        $this->tokenStorage->deleteAccessToken($userId, $accessToken);

        $accessToken = AccessToken::fromRefreshResponse(
            $provider,
            $this->dateTime,
            $response->json(),
            // provide the old AccessToken to borrow some fields if the server
            // does not provide them on "refresh"
            $accessToken
        );

        // store the refreshed AccessToken
        $this->tokenStorage->storeAccessToken($userId, $accessToken);

        return $accessToken;
    }

    private function getAccessToken(Provider $provider, $userId, $scope) {
        $accessTokenList = $this->tokenStorage->getAccessTokenList($userId);
        foreach ($accessTokenList as $accessToken) {
            if ($provider->getProviderId() !== $accessToken->getProviderId()) {
                continue;
            }
            if ($scope !== $accessToken->getScope()) {
                continue;
            }

            return $accessToken;
        }

        return false;
    }

    /**
     * @param string $authUser
     * @param string $authPass
     *
     * @return array
     */
    private static function getAuthorizationHeader($authUser, $authPass) {
        return [
            'Accept' => 'application/json',
            'Authorization' => \sprintf(
                'Basic %s',
                base64_encode(
                    \sprintf('%s:%s', $authUser, $authPass)
                )
            ),
        ];
    }
}
