<?php

declare(strict_types=1);

namespace OnPay\OAuth;

use League\OAuth2\Client\OptionProvider\PostAuthOptionProvider;
use OnPay\API\Util\DataReader;

/**
 * Builds the token endpoint request the way the OnPay authorization server expects it
 * from a public client: form-encoded parameters without a `client_secret`, a JSON
 * Accept header and HTTP Basic credentials carrying only the client_id.
 *
 * A `code_verifier` is only present when PKCE was used, in which case league adds the
 * real verifier to the parameters before this runs (see
 * {@see \OnPay\OnPayAPI::finishAuthorize()}, which feeds it back via `setPkceCode()`).
 * Without PKCE no verifier is sent at all.
 *
 * @internal Shall not be used outside the library.
 */
class OnPayOptionProvider extends PostAuthOptionProvider {
    /**
     * @param string $method
     * @param array<array-key,mixed> $params
     *
     * @return array<array-key,mixed>
     */
    public function getAccessTokenOptions($method, array $params) {
        $clientId = DataReader::stringOrNull($params, 'client_id');
        if (null === $clientId) {
            throw new \InvalidArgumentException('client_id is required for the OnPay token endpoint');
        }
        unset($params['client_secret']);

        $options = parent::getAccessTokenOptions($method, $params);
        $headers = DataReader::arrayOr($options, 'headers');
        $headers['Accept'] = 'application/json';
        $headers['Authorization'] = 'Basic ' . \base64_encode($clientId . ':');
        $options['headers'] = $headers;

        return $options;
    }
}
