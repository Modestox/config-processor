<?php

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Validator\SystemConfig;

use Modestox\ConfigProcessor\Validator\ValidatorInterface;
use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Class Groups
 *
 * Validates logical group wrappers that hold atomic configuration fields.
 */
class Groups implements ValidatorInterface
{
    /**
     * Groups constructor.
     * Injects the child Fields validator using DI.
     */
    public function __construct(
        private ?Fields $fieldsValidator = null
    ) {
        $this->fieldsValidator = $this->fieldsValidator ?? new Fields();
    }

    /**
     * Validates and sanitizes the tabs list with fallback rules.
     *
     * @param array $groups
     * @param array $context
     * @return array<string, mixed>
     * @throws InvalidConfigException
     */
    public function validate(array $groups, array $context = []): array
    {
        $cleanGroups = [];

        foreach ($groups as $groupId => $groupData) {
            if (!is_string($groupId)) {
                throw new InvalidConfigException("The group identifier key must be a valid string.");
            }

            if (!preg_match('/^[a-z0-9_]+$/', $groupId)) {
                throw new InvalidConfigException("The group identifier key '{$groupId}' contains invalid characters. Only lower-case alphanumeric characters and underscores are allowed (a-z, 0-9, _).");
            }

            if (!is_array($groupData)) {
                throw new InvalidConfigException("Configuration for group '{$groupId}' must be an array.");
            }

            // 1. Validate and sanitize optional 'label'
            $label = $groupId;
            if (isset($groupData['label'])) {
                if (!is_string($groupData['label'])) {
                    throw new InvalidConfigException("Field 'label' for group '{$groupId}' must be a strict string.");
                }

                $trimmedLabel = trim($groupData['label']);
                if ($trimmedLabel !== '') {
                    $label = $trimmedLabel;
                }
            }

            // 2. Validate 'sort_order'
            $sortOrder = 0;
            if (isset($groupData['sort_order'])) {
                if (!is_int($groupData['sort_order'])) {
                    throw new InvalidConfigException("Field 'sort_order' for group '{$groupId}' must be a strict integer.");
                }
                $sortOrder = $groupData['sort_order'];
            }

            $cleanGroups[$groupId] = [
                'label'      => $label,
                'sort_order' => $sortOrder,
            ];

            // 3. CASCADING STEP: Forward inner fields to the specialized Fields validator
            if (isset($groupData['fields'])) {
                if (!is_array($groupData['fields'])) {
                    throw new InvalidConfigException("The 'fields' under group '{$groupId}' must be an array.");
                }
                $cleanGroups[$groupId]['fields'] = $this->fieldsValidator->validate($groupData['fields']);
            } else {
                $cleanGroups[$groupId]['fields'] = [];
            }
        }

        uasort($cleanGroups, fn(array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);

        return $cleanGroups;
    }
}