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

/**
 * Class Tabs
 *
 * Validates top-level tabs.
 */
class Tabs extends AbstractNodeValidator
{
    /** @inheritDoc */
    protected function entity(): string
    {
        return 'tab';
    }

    /** @inheritDoc */
    protected function validateNode(string $id, array $data, array $context): array
    {
        $this->assertKnownKeys($data, ['label', 'sort_order', 'class', 'icon'], $id);

        return [
            'label'      => $this->validateLabel($data, $id),
            'sort_order' => $this->validateSortOrder($data, $id),
            'class'      => $this->validateOptionalString($data, 'class', $id),
            'icon'       => $this->validateOptionalString($data, 'icon', $id, 'a valid string layout modifier'),
        ];
    }
}