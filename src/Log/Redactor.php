<?php

declare(strict_types=1);

namespace OnPay\Log;

/**
 * Strips secrets from HTTP messages before they reach a log.
 *
 * The Authorization header and the OAuth material and secrets the API is known
 * to carry in JSON or form-encoded bodies are replaced with a placeholder. Bodies
 * that cannot be parsed are dropped entirely rather than logged verbatim.
 *
 * @internal Shall not be used outside the library.
 */
final class Redactor {
    public const REDACTED = '[redacted]';

    private const SENSITIVE_HEADERS = [
        'authorization',
    ];

    private const SENSITIVE_KEYS = [
        // OAuth token endpoint (request and response)
        'access_token',
        'refresh_token',
        'code',
        'code_verifier',
        // Payment window integration settings
        'secret',
    ];

    /**
     * @param array<string,string> $headers
     *
     * @return array<string,string>
     */
    public function redactHeaders(array $headers): array {
        $redacted = [];
        foreach ($headers as $name => $value) {
            $redacted[$name] = \in_array(\strtolower($name), self::SENSITIVE_HEADERS, true) ? self::REDACTED : $value;
        }

        return $redacted;
    }

    /**
     * Redacts sensitive fields from a JSON or form-encoded body. Any other body is
     * replaced by a placeholder stating only its length.
     *
     * @param array<string,string> $headers the message headers, used to detect form-encoded bodies
     */
    public function redactBody(?string $body, array $headers = []): ?string {
        if (null === $body || '' === $body) {
            return $body;
        }

        /** @var mixed $decoded */
        $decoded = \json_decode($body, true);
        if (\is_array($decoded)) {
            return self::encode($this->redactData($decoded));
        }

        if ($this->isFormEncoded($headers)) {
            \parse_str($body, $fields);

            return \http_build_query($this->redactData($fields), '', '&');
        }

        return \sprintf('[%d bytes of non-JSON body omitted]', \strlen($body));
    }

    /**
     * Recursively redacts values stored under sensitive keys.
     *
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    public function redactData(array $data): array {
        /** @var mixed $value */
        foreach ($data as $key => $value) {
            if (\is_string($key) && $this->isSensitiveKey($key)) {
                $data[$key] = self::REDACTED;
            } elseif (\is_array($value)) {
                $data[$key] = $this->redactData($value);
            }
        }

        return $data;
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private static function encode(array $data): string {
        $json = \json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);

        // @codeCoverageIgnoreStart
        if (false === $json) {
            return '[unencodable body omitted]';
        }
        // @codeCoverageIgnoreEnd

        return $json;
    }

    private function isSensitiveKey(string $key): bool {
        return \in_array(\strtolower($key), self::SENSITIVE_KEYS, true);
    }

    /**
     * @param array<string,string> $headers
     */
    private function isFormEncoded(array $headers): bool {
        foreach ($headers as $name => $value) {
            if ('content-type' === \strtolower($name)) {
                return false !== \stripos($value, 'application/x-www-form-urlencoded');
            }
        }

        return false;
    }
}
