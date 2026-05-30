<?php

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Schema;

use Modestox\ConfigProcessor\Validator\SystemConfig\Tabs;
use Modestox\ConfigProcessor\Validator\SystemConfig\Sections;
use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Class LayoutSchema
 *
 * Represents the standard admin panel structural configuration layout.
 */
class SystemConfig implements SchemaInterface
{
    /**
     * LayoutSchema constructor.
     * Injects required sub-validators via DI.
     */
    public function __construct(
        private ?Tabs $tabs = null,
        private ?Sections $sections = null,
    ) {
        $this->tabs = $this->tabs ?? new Tabs();
        $this->sections = $this->sections ?? new Sections();
    }

    /**
     * Traverses and cross-validates the layout configuration data tree structure.
     *
     * @param array<string, mixed> $dirtyData
     * @return array<string, mixed>
     * @throws InvalidConfigException
     */
    public function validate(array $dirtyData): array
    {
        $cleanData = [];
        $validTabIds = [];

        // 1. Always validate top-level configuration tabs first
        if (isset($dirtyData['tabs'])) {
            if (!is_array($dirtyData['tabs'])) {
                throw new InvalidConfigException("The 'tabs' section must be an array.");
            }
            $cleanData['tabs'] = $this->tabs->validate($dirtyData['tabs']);

            // Compile verified tab identifiers for subsequent cross-validation steps
            $validTabIds = array_keys($cleanData['tabs']);
        }

        // 2. Process sections by injecting validated tab constraints context
        if (isset($dirtyData['sections'])) {
            if (!is_array($dirtyData['sections'])) {
                throw new InvalidConfigException("The 'sections' section must be an array.");
            }

            // Execute isolated validator passing compiled environmental data
            $cleanData['sections'] = $this->sections->validate($dirtyData['sections'], [
                'valid_tabs' => $validTabIds,
            ]);
        }

        return $cleanData;
    }
}