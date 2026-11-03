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
use Modestox\ConfigProcessor\Validator\SystemConfig\Groups;

/**
 * Class GroupedConfig
 *
 * Schema with groups and fields only.
 */
class GroupedConfig implements SchemaInterface
{
    private readonly Groups $groups;

    /**
     * Creates the schema.
     */
    public function __construct(?Groups $groups = null)
    {
        $this->groups = $groups ?? new Groups();
    }

    /** @inheritDoc */
    public function validate(array $dirtyData): array
    {
        if (!isset($dirtyData['groups'])) {
            throw new InvalidConfigException("Invalid configuration root structure. The 'groups' key is required for GroupedConfig.");
        }

        if (!is_array($dirtyData['groups'])) {
            throw new InvalidConfigException("The 'groups' section must be an array.");
        }

        return ['groups' => $this->groups->validate($dirtyData['groups'])];
    }
}