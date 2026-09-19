<?php

declare(strict_types=1);

namespace OnPay\OAuth;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use OnPay\API\Util\DataReader;
use Psr\Http\Message\ResponseInterface;

/**
 * league/oauth2-client provider for the OnPay authorization server.
 *
 * OnPay issues public (secret-less) clients, so the token endpoint is called with
 * the bare client_id and a `Basic client_id:` header (see {@see OnPayOptionProvider}),
 * requests the `full` scope by default and uses PKCE (S256).
 *
 * Options (in addition to the AbstractProvider ones):
 *  - `urlAuthorize`   the authorization endpoint, e.g. https://manage.onpay.io/oauth2/authorize
 *  - `urlAccessToken` the token endpoint, e.g. https://api.onpay.io/oauth2/access_token
 *
 * @internal Shall not be used outside the library.
 *
 * @psalm-suppress PropertyNotSetInConstructor AbstractProvider fills its properties from $options via GuardedPropertyTrait
 */
class OnPayProvider extends AbstractProvider {
    use BearerAuthorizationTrait;

    const DEFAULT_SCOPE = 'full';

    protected string $urlAuthorize;

    protected string $urlAccessToken;

    /**
     * @param array<string,mixed> $options
     * @param array<string,mixed> $collaborators
     */
    public function __construct(array $options = [], array $collaborators = []) {
        $urlAuthorize = DataReader::stringOrNull($options, 'urlAuthorize');
        $urlAccessToken = DataReader::stringOrNull($options, 'urlAccessToken');
        if (null === $urlAuthorize || null === $urlAccessToken) {
            throw new \InvalidArgumentException('Required options "urlAuthorize" and "urlAccessToken" must be strings');
        }
        $this->urlAuthorize = $urlAuthorize;
        $this->urlAccessToken = $urlAccessToken;
        unset($options['urlAuthorize'], $options['urlAccessToken']);

        if (empty($collaborators['optionProvider'])) {
            $collaborators['optionProvider'] = new OnPayOptionProvider();
        }

        parent::__construct($options, $collaborators);
    }

    /**
     * @return string
     */
    public function getBaseAuthorizationUrl() {
        return $this->urlAuthorize;
    }

    /**
     * @param array<array-key,mixed> $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params) {
        return $this->urlAccessToken;
    }

    /**
     * OnPay does not expose a resource owner (userinfo) endpoint.
     *
     * @return never
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token) {
        throw new \LogicException('OnPay does not expose a resource owner endpoint');
    }

    /**
     * @return string[]
     */
    protected function getDefaultScopes() {
        return [self::DEFAULT_SCOPE];
    }

    /**
     * @return string
     */
    protected function getPkceMethod() {
        return self::PKCE_METHOD_S256;
    }

    /**
     * Drops league's non-standard `approval_prompt`, which OnPay does not know.
     *
     * @param array<array-key,mixed> $options
     *
     * @return array<array-key,mixed>
     */
    protected function getAuthorizationParameters(array $options) {
        $params = parent::getAuthorizationParameters($options);
        unset($params['approval_prompt']);

        return $params;
    }

    /**
     * @param array|string $data
     *
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data) {
        $error = \is_array($data) ? DataReader::stringOrNull($data, 'error') : null;
        if (null !== $error) {
            throw new IdentityProviderException($error, $response->getStatusCode(), $data);
        }

        if ($response->getStatusCode() >= 400) {
            throw new IdentityProviderException(
                \sprintf('token endpoint responded HTTP %d', $response->getStatusCode()),
                $response->getStatusCode(),
                $data
            );
        }

        if (!\is_array($data) || null === DataReader::stringOrNull($data, 'access_token')) {
            throw new IdentityProviderException(
                'token endpoint response is missing "access_token"',
                $response->getStatusCode(),
                $data
            );
        }
    }

    /**
     * @param array<array-key,mixed> $response
     *
     * @return never
     */
    protected function createResourceOwner(array $response, AccessToken $token) {
        throw new \LogicException('OnPay does not expose a resource owner endpoint');
    }
}
