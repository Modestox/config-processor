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
 * Class Password
 *
 * Validates password fields.
 */
class Password extends AbstractFieldValidator
{
    /** @inheritDoc */
    protected function specificKeys(): array
    {
        return ['default'];
    }

    /** @inheritDoc */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        // Trimmed to avoid accidental copy-paste whitespace.
        $specific = [
            'default' => $this->validateString($fieldData, 'default', $fieldId, ''),
        ];
        return array_merge($baseMeta, $specific, $this->validateCommon($fieldId, $fieldData));
    }
}