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

use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Class Multiselect
 *
 * Validates and structures configuration components for multi-selection option lists.
 */
class Multiselect extends AbstractFieldValidator
{
    /**
     * @inheritDoc
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        // 1. Validate mandatory 'options' matrix
        if (!isset($fieldData['options']) || !is_array($fieldData['options']) || $fieldData['options'] === []) {
            throw new InvalidConfigException("Field '{$fieldId}' of type 'multiselect' must contain a non-empty array of 'options'.");
        }

        $cleanOptions = [];
        foreach ($fieldData['options'] as $value => $label) {
            if (!is_string($value) && !is_int($value)) {
                throw new InvalidConfigException("Option keys for multiselect field '{$fieldId}' must be strict strings or integers.");
            }
            if (!is_string($label)) {
                throw new InvalidConfigException("Option display text for value '{$value}' in field '{$fieldId}' must be a valid string.");
            }
            $cleanOptions[$value] = trim($label);
        }

        // 2. Validate 'default' parameter - STRICTLY an array
        $default = [];
        if (isset($fieldData['default'])) {
            if (!is_array($fieldData['default'])) {
                throw new InvalidConfigException("Field 'default' for multiselect field '{$fieldId}' must be a strict array of keys.");
            }

            foreach ($fieldData['default'] as $index => $defaultKey) {
                if (!is_string($defaultKey) && !is_int($defaultKey)) {
                    throw new InvalidConfigException(
                        "Default value at index {$index} for multiselect field '{$fieldId}' must be a strict string or integer.",
                    );
                }

                if (!array_key_exists($defaultKey, $cleanOptions)) {
                    throw new InvalidConfigException(
                        "The default value '{$defaultKey}' for field '{$fieldId}' does not exist within the permitted options scope.",
                    );
                }
            }
            $default = $fieldData['default'];
        }

        // Merge base, specific options, and shared common fields (comment, class, validation, required)
        return array_merge($baseMeta, [
            'options' => $cleanOptions,
            'default' => $default,
        ], $this->validateCommon($fieldId, $fieldData));
    }
}