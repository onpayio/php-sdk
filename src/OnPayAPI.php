<?php
declare(strict_types=1);

namespace OnPay;


use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;

class OnPayAPI {
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    protected $options = [];

    protected $oauth2Provider;
    public function __construct(TokenStorageInterface $tokenStorage, array $options) {
        $this->tokenStorage = $tokenStorage;

        $defaultOptions = [
            'base_uri' => 'https://api.onpay.io',
            'base_authorize_uri' => 'https://manage.onpay.io',
        ];

        $requiredOptions = [
            'client_id',
            'redirect_uri',
            'gateway_id'
        ];

        $missing = array_diff_key(array_flip($requiredOptions), $options);
        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Required options not defined: ' . implode(', ', array_keys($missing))
            );
        }

        $this->options = array_merge($defaultOptions, $options);

        $this->oauth2Provider = new GenericProvider([
            'clientId' => $this->options['client_id'],
            'redirectUri' => $this->options['redirect_uri'],
            'urlAuthorize' => $this->options['base_authorize_uri'] . '1978551/oauth2/authorize',
            'urlAccessToken' => $this->options['base_uri'] . 'oauth2/access_token',
            'urlResourceOwnerDetails' => 'http://manage.onlinepay.io/',
            'scopes' => ['full'],
        ]);

    }

    protected function getAccessToken(): ?AccessToken {
        $options = json_decode($this->tokenStorage->getToken(), true);
        if (null !== $options) {
            $accessToken = new AccessToken($options);
            return $accessToken;
        }
        return null;
    }

    protected function refreshToken() {
        $oldAccessToken = $this->getAccessToken();
        $accessToken = $this->oauth2Provider->getAccessToken('refresh_token', [
            'refresh_token' => $oldAccessToken->getRefreshToken(),
        ]);

        $this->tokenStorage->saveToken(json_encode($accessToken));
    }

    /**
     * Checks if we have a Token that looks valid.
     * If it is expired, will attempt to renew it.
     *
     * @return bool
     */
    public function isAuthorized(): bool {
        $accessToken = $this->getAccessToken();
        if (null === $accessToken) {
            return false;
        }

        if ($accessToken->getExpires() < time()) {
            // Token expired, attempt to refresh it
            $this->refreshToken();
        }

        return true;
    }

    /**
     * Returns a URL the user should be redirected to, for authorizing.
     *
     * @return string
     */
    public function authorize(): string {
        return $this->oauth2Provider->getAuthorizationUrl();
    }

    public function finnishAuthorize(string $code) {
        $accessToken = $this->oauth2Provider->getAccessToken('authorization_code',[
            'code' => $code,
        ]);

        $this->tokenStorage->saveToken(json_encode($accessToken));
    }

    public function ping() {
        $request = $this->oauth2Provider->getAuthenticatedRequest(
            'GET',
            $this->options['base_uri'] . '/v1/ping',
            $this->getAccessToken()
        );

        $client = new Client();
        $response = $client->send($request);

        return $response->getBody()->getContents();
    }
}
