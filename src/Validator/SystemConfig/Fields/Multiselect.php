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
class Multiselect implements FieldValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        // 1. Validate mandatory 'options' matrix (Exactly like standard Select)
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

        // 2. Validate 'default' parameter - STRICTLY must be an array for multiselect
        $default = [];
        if (isset($fieldData['default'])) {
            if (!is_array($fieldData['default'])) {
                throw new InvalidConfigException("Field 'default' for multiselect field '{$fieldId}' must be a strict array of keys.");
            }

            // Cross-reference integrity check: every default element MUST exist in choices pool
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

        // 3. Validate standard optional text UI modifiers
        $comment = '';
        if (isset($fieldData['comment'])) {
            if (!is_string($fieldData['comment'])) {
                throw new InvalidConfigException("Field 'comment' for multiselect field '{$fieldId}' must be a strict string.");
            }
            $comment = trim($fieldData['comment']);
        }

        $class = '';
        if (isset($fieldData['class'])) {
            if (!is_string($fieldData['class'])) {
                throw new InvalidConfigException("Field 'class' for multiselect field '{$fieldId}' must be a valid string.");
            }
            $class = trim($fieldData['class']);
        }

        return array_merge($baseMeta, [
            'options' => $cleanOptions,
            'default' => $default,
            'comment' => $comment,
            'class'   => $class,
        ]);
    }
}