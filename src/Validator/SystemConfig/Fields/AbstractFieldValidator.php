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
 * Provides shared field validation helpers.
 */
abstract class AbstractFieldValidator implements FieldValidatorInterface
{
    private const COMMON_KEYS = [
        'type', 'label', 'sort_order', 'depends', 'comment', 'class', 'validation', 'required', 'provider',
    ];

    /** @inheritDoc */
    final public function allowedKeys(): array
    {
        return array_merge(self::COMMON_KEYS, $this->specificKeys());
    }

    /**
     * Returns the keys specific to this field type.
     *
     * @return list<string>
     */
    protected function specificKeys(): array
    {
        return [];
    }

    /**
     * Validates the non-empty 'options' map of choice fields.
     *
     * @param array<string, mixed> $fieldData
     * @return array<int|string, string>
     * @throws InvalidConfigException
     */
    protected function validateOptions(array $fieldData, string $fieldId, string $type): array
    {
        if (!isset($fieldData['options']) || !is_array($fieldData['options']) || $fieldData['options'] === []) {
            throw new InvalidConfigException("Field '{$fieldId}' of type '{$type}' must contain a non-empty array of 'options'.");
        }

        $options = [];
        foreach ($fieldData['options'] as $value => $label) {
            if (!is_string($value) && !is_int($value)) {
                throw new InvalidConfigException("Option keys for {$type} field '{$fieldId}' must be strict strings or integers.");
            }
            if (!is_string($label)) {
                throw new InvalidConfigException("Option display text for value '{$value}' in field '{$fieldId}' must be a valid string.");
            }
            $options[$value] = trim($label);
        }

        return $options;
    }

    /**
     * Validates common field attributes.
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

    /**
     * Validates an optional string.
     */
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

    /**
     * Validates an optional boolean.
     */
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

    /**
     * Validates an optional list of strings.
     */
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