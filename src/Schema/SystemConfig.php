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
use Modestox\ConfigProcessor\Validator\SystemConfig\Sections;
use Modestox\ConfigProcessor\Validator\SystemConfig\Tabs;

/**
 * Class SystemConfig
 *
 * Schema with tabs, sections, groups and fields.
 */
class SystemConfig implements SchemaInterface
{
    private readonly Tabs $tabs;
    private readonly Sections $sections;

    /**
     * Creates the schema.
     */
    public function __construct(?Tabs $tabs = null, ?Sections $sections = null)
    {
        $this->tabs = $tabs ?? new Tabs();
        $this->sections = $sections ?? new Sections();
    }

    /** @inheritDoc */
    public function validate(array $dirtyData): array
    {
        $cleanData = [];
        $validTabIds = [];

        if (isset($dirtyData['tabs'])) {
            if (!is_array($dirtyData['tabs'])) {
                throw new InvalidConfigException("The 'tabs' section must be an array.");
            }
            $cleanData['tabs'] = $this->tabs->validate($dirtyData['tabs']);
            $validTabIds = array_keys($cleanData['tabs']);
        }

        if (isset($dirtyData['sections'])) {
            if (!is_array($dirtyData['sections'])) {
                throw new InvalidConfigException("The 'sections' section must be an array.");
            }
            $cleanData['sections'] = $this->sections->validate($dirtyData['sections'], [
                'valid_tabs' => $validTabIds,
            ]);
        }

        return $cleanData;
    }
}