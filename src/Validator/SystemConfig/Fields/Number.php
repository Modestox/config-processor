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
 * Validates numeric fields.
 */
class Number extends AbstractFieldValidator
{
    /** @inheritDoc */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        $min = $this->validateNumber($fieldData, 'min', $fieldId);
        $max = $this->validateNumber($fieldData, 'max', $fieldId);

        if ($min !== null && $max !== null && $max < $min) {
            throw new InvalidConfigException("Field 'max' cannot be less than 'min' for number field '{$fieldId}'.");
        }

        $default = $this->validateNumber($fieldData, 'default', $fieldId);

        if ($default === null) {
            // Zero is the natural fallback, unless the declared range excludes it.
            $default = 0;
            if ($min !== null && $min > 0) {
                $default = $min;
            } elseif ($max !== null && $max < 0) {
                $default = $max;
            }
        } else {
            if ($min !== null && $default < $min) {
                throw new InvalidConfigException("Default value for number field '{$fieldId}' cannot be less than defined 'min' restriction.");
            }
            if ($max !== null && $default > $max) {
                throw new InvalidConfigException("Default value for number field '{$fieldId}' cannot be greater than defined 'max' restriction.");
            }
        }

        $specific = [
            'default' => $default,
            'min'     => $min,
            'max'     => $max,
        ];

        return array_merge($baseMeta, $specific, $this->validateCommon($fieldId, $fieldData));
    }

    /**
     * Validates an optional finite number.
     */
    private function validateNumber(array $data, string $key, string $fieldId): int|float|null
    {
        if (!isset($data[$key])) {
            return null;
        }

        $value = $data[$key];

        if (!is_int($value) && !is_float($value)) {
            throw new InvalidConfigException("Field '{$key}' for number field '{$fieldId}' must be a strict integer or float.");
        }
        if (is_float($value) && !is_finite($value)) {
            throw new InvalidConfigException("Field '{$key}' for number field '{$fieldId}' must be a finite number.");
        }

        return $value;
    }
}