<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Validator\SystemConfig;

use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Class Sections
 *
 * Validates sections and delegates groups validation.
 */
class Sections extends AbstractNodeValidator
{
    private readonly Groups $groupsValidator;

    /**
     * Creates the validator.
     */
    public function __construct(?Groups $groupsValidator = null)
    {
        $this->groupsValidator = $groupsValidator ?? new Groups();
    }

    /** @inheritDoc */
    protected function entity(): string
    {
        return 'section';
    }

    /**
     * @inheritDoc
     *
     * Expects the list of known tab identifiers in $context['valid_tabs'].
     */
    protected function validateNode(string $id, array $data, array $context): array
    {
        $this->assertKnownKeys($data, ['tab', 'label', 'sort_order', 'class', 'icon', 'groups'], $id);

        if (!isset($data['tab'])) {
            throw new InvalidConfigException("Section '{$id}' is missing its mandatory 'tab' assignment parameter.");
        }
        if (!is_string($data['tab'])) {
            throw new InvalidConfigException("Field 'tab' for section '{$id}' must be a strict string.");
        }

        $tab = trim($data['tab']);
        $validTabs = $context['valid_tabs'] ?? [];

        if (!in_array($tab, $validTabs, true)) {
            $defined = $validTabs === [] ? 'No tabs are defined.' : 'Defined tabs: ' . implode(', ', $validTabs) . '.';
            throw new InvalidConfigException("Section '{$id}' references an undefined parent tab '{$tab}'. {$defined}");
        }

        $label = $this->validateLabel($data, $id);
        $sortOrder = $this->validateSortOrder($data, $id);
        $class = $this->validateOptionalString($data, 'class', $id);
        $icon = $this->validateOptionalString($data, 'icon', $id, 'a valid string layout modifier');

        $groups = [];
        if (isset($data['groups'])) {
            if (!is_array($data['groups'])) {
                throw new InvalidConfigException("The 'groups' under section '{$id}' must be an array.");
            }

            try {
                $groups = $this->groupsValidator->validate($data['groups']);
            } catch (InvalidConfigException $e) {
                throw $e->prependPath('groups');
            }
        }

        return [
            'tab'        => $tab,
            'label'      => $label,
            'sort_order' => $sortOrder,
            'class'      => $class,
            'icon'       => $icon,
            'groups'     => $groups,
        ];
    }
}