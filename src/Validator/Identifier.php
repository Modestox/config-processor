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
 * Class Identifier
 *
 * Validates configuration node identifiers.
 */
final class Identifier
{
    private const PATTERN = '/^[a-z0-9_]+$/D';

    /**
     * Asserts that a node key is a valid identifier.
     *
     * @throws InvalidConfigException
     */
    public static function assert(string|int $key, string $entity): string
    {
        if (!is_string($key)) {
            throw new InvalidConfigException(
                "The {$entity} identifier key must be a valid string. Numeric keys are cast to integers by PHP and are not supported.",
            );
        }

        if (!preg_match(self::PATTERN, $key)) {
            throw new InvalidConfigException(
                "The {$entity} identifier key '{$key}' contains invalid characters. Only lower-case alphanumeric characters and underscores are allowed (a-z, 0-9, _).",
            );
        }

        return $key;
    }
}
