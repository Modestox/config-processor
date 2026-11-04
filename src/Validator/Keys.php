<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Validator;

use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Class Keys
 *
 * Checks node keys against the allowed list and custom 'x_' extensions.
 */
final class Keys
{
    private const EXTENSION_PATTERN = '/^x_[a-z0-9_]+$/D';

    /**
     * Rejects keys that are neither allowed nor 'x_' extensions.
     *
     * @param array<array-key, mixed> $data
     * @param list<string> $allowed
     * @param string $where Location for the message, e.g. "field 'site_name'".
     * @throws InvalidConfigException
     */
    public static function assertKnown(array $data, array $allowed, string $where): void
    {
        foreach (array_keys($data) as $key) {
            if (in_array($key, $allowed, true) || self::isExtension($key)) {
                continue;
            }

            $message = "Unknown key '{$key}' in {$where}.";

            $suggestion = self::suggest((string)$key, $allowed);
            if ($suggestion !== null) {
                $message .= " Did you mean '{$suggestion}'?";
            }

            throw new InvalidConfigException(
                $message . ' Allowed keys: ' . implode(', ', $allowed) . ". Custom keys must start with 'x_'.",
            );
        }
    }

    /**
     * Tells whether the key is a custom extension key.
     */
    public static function isExtension(string|int $key): bool
    {
        return is_string($key) && preg_match(self::EXTENSION_PATTERN, $key) === 1;
    }

    /**
     * Returns the custom 'x_' keys of a node.
     *
     * @param array<array-key, mixed> $data
     * @return array<string, mixed>
     */
    public static function extensions(array $data): array
    {
        return array_filter($data, static fn(string|int $key): bool => self::isExtension($key), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Finds the closest allowed key for a typo.
     *
     * @param list<string> $allowed
     */
    private static function suggest(string $key, array $allowed): ?string
    {
        $best = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($allowed as $candidate) {
            $distance = levenshtein($key, $candidate);
            if ($distance < $bestDistance) {
                $best = $candidate;
                $bestDistance = $distance;
            }
        }

        if ($best === null) {
            return null;
        }

        return $bestDistance <= max(1, intdiv(strlen($best), 3)) ? $best : null;
    }
}