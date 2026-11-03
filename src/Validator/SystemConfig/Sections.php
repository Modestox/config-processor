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
use Modestox\ConfigProcessor\Validator\Identifier;
use Modestox\ConfigProcessor\Validator\ValidatorInterface;

/**
 * Class Sections
 *
 * Validates sections and delegates groups validation.
 */
class Sections implements ValidatorInterface
{
    private readonly Groups $groupsValidator;

    /**
     * Sections constructor.
     */
    public function __construct(?Groups $groupsValidator = null)
    {
        $this->groupsValidator = $groupsValidator ?? new Groups();
    }

    /**
     * @inheritDoc
     *
     * Expects the list of known tab identifiers in $context['valid_tabs'].
     */
    public function validate(array $sections, array $context = []): array
    {
        $cleanSections = [];
        $validTabs = $context['valid_tabs'] ?? [];

        foreach ($sections as $sectionId => $sectionData) {
            $sectionId = Identifier::assert($sectionId, 'section');

            if (!is_array($sectionData)) {
                throw new InvalidConfigException("Configuration for section '{$sectionId}' must be an array.");
            }

            if (!isset($sectionData['tab'])) {
                throw new InvalidConfigException("Section '{$sectionId}' is missing its mandatory 'tab' assignment parameter.");
            }

            if (!is_string($sectionData['tab'])) {
                throw new InvalidConfigException("Field 'tab' for section '{$sectionId}' must be a strict string.");
            }

            $targetTab = trim($sectionData['tab']);

            if (!in_array($targetTab, $validTabs, true)) {
                throw new InvalidConfigException("Section '{$sectionId}' references an undefined parent tab '{$targetTab}'.");
            }

            $label = $sectionId;
            if (isset($sectionData['label'])) {
                if (!is_string($sectionData['label'])) {
                    throw new InvalidConfigException("Field 'label' for section '{$sectionId}' must be a strict string.");
                }

                $trimmedLabel = trim($sectionData['label']);
                if ($trimmedLabel !== '') {
                    $label = $trimmedLabel;
                }
            }

            $sortOrder = 0;
            if (isset($sectionData['sort_order'])) {
                if (!is_int($sectionData['sort_order'])) {
                    throw new InvalidConfigException("Field 'sort_order' for section '{$sectionId}' must be a strict integer.");
                }
                $sortOrder = $sectionData['sort_order'];
            }

            $class = '';
            if (isset($sectionData['class'])) {
                if (!is_string($sectionData['class'])) {
                    throw new InvalidConfigException("Field 'class' for section '{$sectionId}' must be a valid string.");
                }
                $class = trim($sectionData['class']);
            }

            $icon = '';
            if (isset($sectionData['icon'])) {
                if (!is_string($sectionData['icon'])) {
                    throw new InvalidConfigException("Field 'icon' for section '{$sectionId}' must be a valid string layout modifier.");
                }
                $icon = trim($sectionData['icon']);
            }

            $groups = [];
            if (isset($sectionData['groups'])) {
                if (!is_array($sectionData['groups'])) {
                    throw new InvalidConfigException("The 'groups' under section '{$sectionId}' must be an array.");
                }
                $groups = $this->groupsValidator->validate($sectionData['groups']);
            }

            $cleanSections[$sectionId] = [
                'tab'        => $targetTab,
                'label'      => $label,
                'sort_order' => $sortOrder,
                'class'      => $class,
                'icon'       => $icon,
                'groups'     => $groups,
            ];
        }

        uasort($cleanSections, fn(array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);

        return $cleanSections;
    }
}