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
 * Class Boolean
 *
 * Validates and normalizes attributes specific only to the 'boolean' toggle/checkbox field type.
 */
class Boolean extends AbstractFieldValidator
{
    /**
     * @inheritDoc
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        // Boolean type strictly requires 'default' to be a boolean
        $specific = [
            'default' => $this->validateBool($fieldData, 'default', $fieldId, false),
        ];

        // Merge all layers: base metadata, unique attributes, and shared ones
        return array_merge($baseMeta, $specific, $this->validateCommon($fieldId, $fieldData));
    }
}