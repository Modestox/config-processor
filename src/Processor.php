<?php

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
     * Processor constructor.
     * Uses PHP 8.0 Constructor Property Promotion to inject sub-validators.
     */
    public function __construct(
    ) {
        InvalidConfigException::register();
    }

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