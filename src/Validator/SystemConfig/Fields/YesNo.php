<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Validator\SystemConfig\Fields;

/**
 * Class YesNo
 *
 * Validates yes/no select fields.
 */
class YesNo extends Select
{
    /** @inheritDoc */
    protected function specificKeys(): array
    {
        return ['default'];
    }

    /** @inheritDoc */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        // Options are fixed.
        $fieldData['options'] = [
            0 => 'No',
            1 => 'Yes',
        ];

        // Default falls back to 0 (No).
        if (!isset($fieldData['default'])) {
            $fieldData['default'] = 0;
        }

        return parent::validate($fieldId, $fieldData, $baseMeta);
    }
}