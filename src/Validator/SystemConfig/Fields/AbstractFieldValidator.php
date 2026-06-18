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
 * Class AbstractFieldValidator
 *
 * Provides shared validation logic to reduce redundancy across field type strategies.
 */
abstract class AbstractFieldValidator implements FieldValidatorInterface
{
    /**
     * Validates and returns common field attributes.
     */
    protected function validateCommon(string $fieldId, array $fieldData): array
    {
        return [
            'comment'    => $this->validateString($fieldData, 'comment', $fieldId, ''),
            'class'      => $this->validateString($fieldData, 'class', $fieldId, ''),
            'validation' => $this->validateArray($fieldData, 'validation', $fieldId, []),
            'required'   => $this->validateBool($fieldData, 'required', $fieldId, false),
            'provider'   => $this->validateString($fieldData, 'provider', $fieldId, ''),
        ];
    }

    protected function validateString(array $data, string $key, string $fieldId, string $default): string
    {
        if (!isset($data[$key])) {
            return $default;
        }
        if (!is_string($data[$key])) {
            throw new InvalidConfigException("Field '{$key}' for field '{$fieldId}' must be a strict string.");
        }
        return trim($data[$key]);
    }

    protected function validateBool(array $data, string $key, string $fieldId, bool $default): bool
    {
        if (!isset($data[$key])) {
            return $default;
        }
        if (!is_bool($data[$key])) {
            throw new InvalidConfigException("Field '{$key}' for field '{$fieldId}' must be a strict boolean.");
        }
        return $data[$key];
    }

    protected function validateArray(array $data, string $key, string $fieldId, array $default): array
    {
        if (!isset($data[$key])) {
            return $default;
        }
        if (!is_array($data[$key])) {
            throw new InvalidConfigException("Field '{$key}' for field '{$fieldId}' must be an array.");
        }

        $cleanArray = [];
        foreach ($data[$key] as $index => $value) {
            if (!is_string($value)) {
                throw new InvalidConfigException("Validation rule at index '{$index}' for field '{$fieldId}' must be a strict string.");
            }
            $cleanArray[$index] = trim($value);
        }

        return $cleanArray;
    }
}