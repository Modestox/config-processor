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
 * Class Groups
 *
 * Validates groups and delegates fields validation.
 */
class Groups implements ValidatorInterface
{
    private readonly Fields $fieldsValidator;

    /**
     * Groups constructor.
     */
    public function __construct(?Fields $fieldsValidator = null)
    {
        $this->fieldsValidator = $fieldsValidator ?? new Fields();
    }

    /** @inheritDoc */
    public function validate(array $groups, array $context = []): array
    {
        $cleanGroups = [];

        foreach ($groups as $groupId => $groupData) {
            $groupId = Identifier::assert($groupId, 'group');

            if (!is_array($groupData)) {
                throw new InvalidConfigException("Configuration for group '{$groupId}' must be an array.");
            }

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

            $sortOrder = 0;
            if (isset($groupData['sort_order'])) {
                if (!is_int($groupData['sort_order'])) {
                    throw new InvalidConfigException("Field 'sort_order' for group '{$groupId}' must be a strict integer.");
                }
                $sortOrder = $groupData['sort_order'];
            }

            $fields = [];
            if (isset($groupData['fields'])) {
                if (!is_array($groupData['fields'])) {
                    throw new InvalidConfigException("The 'fields' under group '{$groupId}' must be an array.");
                }
                $fields = $this->fieldsValidator->validate($groupData['fields']);
            }

            $cleanGroups[$groupId] = [
                'label'      => $label,
                'sort_order' => $sortOrder,
                'fields'     => $fields,
            ];
        }

        uasort($cleanGroups, fn(array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);

        return $cleanGroups;
    }
}