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
use Modestox\ConfigProcessor\Validator\Keys;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\FieldValidatorInterface;
use Modestox\ConfigProcessor\Validator\SystemConfig\Groups;
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

    /**
     * Creates the schema with additional or overridden field types.
     *
     * @param array<string, FieldValidatorInterface> $validators
     */
    public static function withFieldTypes(array $validators): self
    {
        return new self(null, new Sections(new Groups(new Fields($validators))));
    }

    /** @inheritDoc */
    public function validate(array $dirtyData): array
    {
        Keys::assertKnown($dirtyData, ['tabs', 'sections'], 'the configuration root');

        $cleanData = [];
        $validTabIds = [];

        if (isset($dirtyData['tabs'])) {
            if (!is_array($dirtyData['tabs'])) {
                throw new InvalidConfigException("The 'tabs' section must be an array.");
            }

            try {
                $cleanData['tabs'] = $this->tabs->validate($dirtyData['tabs']);
            } catch (InvalidConfigException $e) {
                throw $e->prependPath('tabs');
            }

            $validTabIds = array_keys($cleanData['tabs']);
        }

        if (isset($dirtyData['sections'])) {
            if (!is_array($dirtyData['sections'])) {
                throw new InvalidConfigException("The 'sections' section must be an array.");
            }

            try {
                $cleanData['sections'] = $this->sections->validate($dirtyData['sections'], ['valid_tabs' => $validTabIds]);
            } catch (InvalidConfigException $e) {
                throw $e->prependPath('sections');
            }
        }

        return array_merge($cleanData, Keys::extensions($dirtyData));
    }
}