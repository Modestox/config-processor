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
 * Act as an orchestrator that delegates tasks to dedicated sub-validators.
 */
class Processor
{
    /**
     * Processes any dirty configuration array based on the provided validation schema.
     *
     * @param array<string, mixed> $dirtyData
     * @param SchemaInterface $schema
     * @return array<string, mixed>
     * @throws InvalidConfigException
     */
    public function process(array $dirtyData, SchemaInterface $schema): array
    {
        return $schema->validate($dirtyData);
    }
}