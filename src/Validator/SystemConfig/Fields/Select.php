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
 * Class Select
 *
 * Validates and structures configuration components for drop-down selection options.
 */
class Select extends AbstractFieldValidator
{
    /**
     * @inheritDoc
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        // 1. Validate mandatory 'options' matrix
        if (!isset($fieldData['options']) || !is_array($fieldData['options']) || $fieldData['options'] === []) {
            throw new InvalidConfigException("Field '{$fieldId}' of type 'select' must contain a non-empty array of 'options'.");
        }

        $cleanOptions = [];
        foreach ($fieldData['options'] as $value => $label) {
            if (!is_string($value) && !is_int($value)) {
                throw new InvalidConfigException("Option keys for select field '{$fieldId}' must be strict strings or integers.");
            }
            if (!is_string($label)) {
                throw new InvalidConfigException("Option display text for value '{$value}' in field '{$fieldId}' must be a valid string.");
            }
            $cleanOptions[$value] = trim($label);
        }

        // 2. Validate 'default' reference integrity
        $default = array_key_first($cleanOptions);
        if (isset($fieldData['default'])) {
            if (!is_string($fieldData['default']) && !is_int($fieldData['default'])) {
                throw new InvalidConfigException("Field 'default' for select field '{$fieldId}' must be a strict string or integer.");
            }

            if (!array_key_exists($fieldData['default'], $cleanOptions)) {
                throw new InvalidConfigException(
                    "The default value '{$fieldData['default']}' for field '{$fieldId}' does not exist within the permitted options scope.",
                );
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