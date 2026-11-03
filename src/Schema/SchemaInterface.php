<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Schema;

use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Interface SchemaInterface
 *
 * Contract for configuration schemas.
 */
interface SchemaInterface
{
    /**
     * Validates and normalizes configuration data.
     *
     * @param array<string, mixed> $dirtyData
     * @return array<string, mixed>
     * @throws InvalidConfigException
     */
    public function validate(array $dirtyData): array;
}