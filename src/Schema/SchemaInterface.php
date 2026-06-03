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

/**
 * Interface SchemaInterface
 *
 * Defines the strict validation and normalization contract for configuration schemas.
 */
interface SchemaInterface
{
    /**
     * @param array<string, mixed> $dirtyData
     * @return array<string, mixed>
     */
    public function validate(array $dirtyData): array;
}