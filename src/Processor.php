<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessor;

use Modestox\ConfigProcessor\Exception\InvalidConfigException;
use Modestox\ConfigProcessor\Schema\SchemaInterface;

/**
 * Class Processor
 *
 * Delegates validation to the given schema.
 */
class Processor
{
    /**
     * Processes raw configuration data.
     *
     * @param array<string, mixed> $dirtyData
     * @return array<string, mixed>
     * @throws InvalidConfigException
     */
    public function process(array $dirtyData, SchemaInterface $schema): array
    {
        return $schema->validate($dirtyData);
    }
}