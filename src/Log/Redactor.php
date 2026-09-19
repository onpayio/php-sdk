<?php

namespace OnPay\Log;

/**
 * Strips secrets from HTTP messages before they reach a log.
 *
 * Credentials carried in headers (Authorization, cookies), OAuth material and
 * cardholder data carried in JSON or form-encoded bodies are replaced with a
 * placeholder. Bodies that cannot be parsed are dropped entirely rather than
 * logged verbatim, so an unrecognised payload can never leak.
 *
 * @internal Shall not be used outside the library.
 */
class Redactor {
    public const REDACTED = '[redacted]';

    private const SENSITIVE_HEADERS = [
        'authorization',
        'proxy-authorization',
        'cookie',
        'set-cookie',
    ];

    private const SENSITIVE_KEYS = [
        // OAuth / credentials
        'access_token',
        'refresh_token',
        'token',
        'id_token',
        'code',
        'code_verifier',
        'client_secret',
        'secret',
        'password',
        'authorization',
        // Cardholder data
        'card_number',
        'cardnumber',
        'card_no',
        'pan',
        'cvc',
        'cvv',
        'cvd',
        'csc',
        'security_code',
        'expiry_month',
        'expiry_year',
        'exp_month',
        'exp_year',
        'track_data',
        'pin',
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
     * Recursively redacts values stored under sensitive keys, and masks any string
     * value that looks like a card number (13-19 digits passing the Luhn check).
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
            } elseif (\is_string($value) && $this->looksLikeCardNumber($value)) {
                $data[$key] = self::REDACTED;
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
        return \in_array(\str_replace('-', '_', \strtolower($key)), self::SENSITIVE_KEYS, true);
    }

    private function looksLikeCardNumber(string $value): bool {
        $digits = \preg_replace('/[\s-]/', '', $value);
        if (null === $digits || 1 !== \preg_match('/^\d{13,19}$/', $digits)) {
            return false;
        }

        $sum = 0;
        $double = false;
        for ($i = \strlen($digits) - 1; $i >= 0; --$i) {
            $digit = (int) $digits[$i];
            if ($double) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
            $double = !$double;
        }

        return 0 === $sum % 10;
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
