<?php

namespace Tests\Support;

/**
 * Loads canned API-response fixtures from tests/Fixtures.
 *
 * Fixtures are stored as JSON in the real API envelope shape so they can be handed to
 * the SDK exactly as the API would return them.
 */
final class FixtureLoader
{
    private const FIXTURE_DIR = __DIR__ . '/../Fixtures';

    /**
     * Return the raw JSON string of a fixture, e.g. FixtureLoader::raw('transaction/detailed').
     */
    public static function raw(string $name): string
    {
        $path = self::FIXTURE_DIR . '/' . $name . '.json';
        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('Fixture "%s" not found at %s', $name, $path));
        }

        $contents = file_get_contents($path);
        if (false === $contents) {
            throw new \RuntimeException(sprintf('Fixture "%s" could not be read', $name));
        }

        return $contents;
    }

    /**
     * Return a fixture decoded to an associative array.
     *
     * @return array<mixed>
     */
    public static function load(string $name): array
    {
        $decoded = json_decode(self::raw($name), true);
        if (!is_array($decoded)) {
            throw new \RuntimeException(sprintf('Fixture "%s" is not a JSON object', $name));
        }

        return $decoded;
    }
}
