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

use Modestox\ConfigProcessor\Validator\SystemConfig\Groups;
use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Class GroupedConfig
 *
 * Represents the lightweight isolated component/plugin configuration layout.
 * Bypasses system-level tabs and sections, working directly with groups and fields.
 */
class GroupedConfig implements SchemaInterface
{
    /**
     * GroupedConfig constructor.
     * Injects required sub-validators via DI with native manual fallback.
     *
     * @param Groups|null $groups
     */
    public function __construct(
        private ?Groups $groups = null,
    ) {
        $this->groups = $this->groups ?? new Groups();
    }

    /**
     * Traverses and validates the standalone grouped configuration data tree structure.
     *
     * @param array<string, mixed> $dirtyData
     * @return array<string, mixed>
     * @throws InvalidConfigException
     */
    public function validate(array $dirtyData): array
    {
        $cleanData = [];

        // 1. Always validate top-level configuration groups first
        if (isset($dirtyData['groups'])) {
            if (!is_array($dirtyData['groups'])) {
                throw new InvalidConfigException("The 'groups' section must be an array.");
            }

            // Execute isolated validator for the groups slice
            $cleanData['groups'] = $this->groups->validate($dirtyData['groups']);
        } else {
            // Guarantee that the root key is present even if empty input was provided
            throw new InvalidConfigException("Invalid configuration root structure. The 'groups' key is required for GroupedConfig.");
        }

        return $cleanData;
    }
}