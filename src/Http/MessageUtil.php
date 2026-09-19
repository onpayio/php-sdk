<?php

declare(strict_types=1);

namespace OnPay\Http;

use Psr\Http\Message\MessageInterface;

/**
 * Helpers that reduce PSR-7 messages to the flat shapes the SDK's debug API and
 * log redaction work on.
 *
 * @internal Shall not be used outside the library.
 */
final class MessageUtil {
    /**
     * Flattens PSR-7 headers (name => list of values) into a name => value map.
     *
     * @return array<string,string>
     */
    public static function flattenHeaders(MessageInterface $message): array {
        $flat = [];
        foreach ($message->getHeaders() as $name => $values) {
            $flat[(string) $name] = \implode(', ', $values);
        }

        return $flat;
    }

    /**
     * The message body as a string, or null when the message carries no body.
     */
    public static function bodyOrNull(MessageInterface $message): ?string {
        $body = (string) $message->getBody();

        return '' === $body ? null : $body;
    }
}
