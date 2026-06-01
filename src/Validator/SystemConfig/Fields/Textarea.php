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
 * Class Textarea
 *
 * Validates and normalizes attributes specific to the multi-line text input field type.
 */
class Textarea extends AbstractFieldValidator
{
    /**
     * @inheritDoc
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        // Only validate parameters unique to the 'textarea' type
        $specific = [
            'default'     => $this->validateString($fieldData, 'default', $fieldId, ''),
            'placeholder' => $this->validateString($fieldData, 'placeholder', $fieldId, ''),
        ];

        // Merge all layers: base metadata, unique attributes, and shared ones
        return array_merge($baseMeta, $specific, $this->validateCommon($fieldId, $fieldData));
    }
}