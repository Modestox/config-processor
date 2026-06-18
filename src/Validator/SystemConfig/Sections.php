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

use Modestox\ConfigProcessor\Validator\ValidatorInterface;
use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Class Sections
 *
 * Validates layout groups and delegates field tree rendering deeper via cascading validation.
 */
class Sections implements ValidatorInterface
{
    /**
     * Sections constructor.
     * Injects the intermediate Groups validator using DI patterns.
     */
    public function __construct(
        private ?Groups $groupsValidator = null
    ) {
        $this->groupsValidator = $this->groupsValidator ?? new Groups();
    }

    /**
     * @inheritDoc
     */
    public function validate(array $sections, array $context = []): array
    {
        $cleanSections = [];
        $validTabs = $context['valid_tabs'] ?? [];

        foreach ($sections as $sectionId => $sectionData) {
            if (!is_string($sectionId)) {
                throw new InvalidConfigException("The section identifier key must be a valid string.");
            }

            if (!preg_match('/^[a-z0-9_]+$/', $sectionId)) {
                throw new InvalidConfigException("The section identifier key '{$sectionId}' contains invalid characters. Only lower-case alphanumeric characters and underscores are allowed (a-z, 0-9, _).");
            }

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

            $cleanSections[$sectionId] = [
                'tab'        => $targetTab,
                'label'      => $label,
                'sort_order' => $sortOrder,
            ];

            if (isset($sectionData['class'])) {
                if (!is_string($sectionData['class'])) {
                    throw new InvalidConfigException("Field 'class' for section '{$sectionId}' must be a valid string.");
                }
                $cleanSections[$sectionId]['class'] = trim($sectionData['class']);
            }

            if (isset($sectionData['icon'])) {
                if (!is_string($sectionData['icon'])) {
                    throw new InvalidConfigException("Field 'icon' for section '{$sectionId}' must be a valid string layout modifier.");
                }
                $cleanSections[$sectionId]['icon'] = trim($sectionData['icon']);
            }

            // NEW CASCADING STEP: Forward execution to Groups instead of Fields
            if (isset($sectionData['groups'])) {
                if (!is_array($sectionData['groups'])) {
                    throw new InvalidConfigException("The 'groups' under section '{$sectionId}' must be an array.");
                }
                $cleanSections[$sectionId]['groups'] = $this->groupsValidator->validate($sectionData['groups']);
            } else {
                $cleanSections[$sectionId]['groups'] = [];
            }
        }

        uasort($cleanSections, fn(array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);

        return $cleanSections;
    }
}