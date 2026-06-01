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
 * Class Number
 *
 * Validates and normalizes attributes specific to the numeric input field type.
 */
class Number extends AbstractFieldValidator
{
    /**
     * @inheritDoc
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        // 1. Validate 'min' restriction if set
        $min = null;
        if (isset($fieldData['min'])) {
            if (!is_int($fieldData['min']) && !is_float($fieldData['min'])) {
                throw new InvalidConfigException("Field 'min' for number field '{$fieldId}' must be a strict integer or float.");
            }
            $min = $fieldData['min'];
        }

        // 2. Validate 'max' restriction if set
        $max = null;
        if (isset($fieldData['max'])) {
            if (!is_int($fieldData['max']) && !is_float($fieldData['max'])) {
                throw new InvalidConfigException("Field 'max' for number field '{$fieldId}' must be a strict integer or float.");
            }
            if ($min !== null && $fieldData['max'] < $min) {
                throw new InvalidConfigException("Field 'max' cannot be less than 'min' for number field '{$fieldId}'.");
            }
            $max = $fieldData['max'];
        }

        // 3. Validate 'default' value (Must be numeric and fit within min/max boundaries if they exist)
        $default = 0;
        if (isset($fieldData['default'])) {
            if (!is_int($fieldData['default']) && !is_float($fieldData['default'])) {
                throw new InvalidConfigException("Field 'default' for number field '{$fieldId}' must be a strict integer or float.");
            }

            $defaultVal = $fieldData['default'];
            if ($min !== null && $defaultVal < $min) {
                throw new InvalidConfigException("Default value for number field '{$fieldId}' cannot be less than defined 'min' restriction.");
            }
            if ($max !== null && $defaultVal > $max) {
                throw new InvalidConfigException("Default value for number field '{$fieldId}' cannot be greater than defined 'max' restriction.");
            }
            $default = $defaultVal;
        }

        $specific = [
            'default' => $default,
            'min'     => $min,
            'max'     => $max,
        ];

        return array_merge($baseMeta, $specific, $this->validateCommon($fieldId, $fieldData));
    }
}