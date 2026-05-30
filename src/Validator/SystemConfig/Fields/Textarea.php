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
 * Class Textarea
 *
 * Validates and normalizes attributes specific to the multi-line text input field type.
 */
class Textarea implements FieldValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        // 1. Validate 'default' (Must be a strict string if provided)
        $default = '';
        if (isset($fieldData['default'])) {
            if (!is_string($fieldData['default'])) {
                throw new InvalidConfigException("Field 'default' for textarea field '{$fieldId}' must be a strict string.");
            }
            $default = trim($fieldData['default']);
        }

        // 2. Validate UI helper modifier: 'placeholder'
        $placeholder = '';
        if (isset($fieldData['placeholder'])) {
            if (!is_string($fieldData['placeholder'])) {
                throw new InvalidConfigException("Field 'placeholder' for textarea field '{$fieldId}' must be a strict string.");
            }
            $placeholder = trim($fieldData['placeholder']);
        }

        // 3. Validate UI layout modifier: 'comment'
        $comment = '';
        if (isset($fieldData['comment'])) {
            if (!is_string($fieldData['comment'])) {
                throw new InvalidConfigException("Field 'comment' for textarea field '{$fieldId}' must be a strict string.");
            }
            $comment = trim($fieldData['comment']);
        }

        // 4. Validate CSS class modifier: 'class'
        $class = '';
        if (isset($fieldData['class'])) {
            if (!is_string($fieldData['class'])) {
                throw new InvalidConfigException("Field 'class' for textarea field '{$fieldId}' must be a valid string.");
            }
            $class = trim($fieldData['class']);
        }

        // 5. Validate input rules stack: 'validation'
        $validation = [];
        if (isset($fieldData['validation'])) {
            if (!is_array($fieldData['validation'])) {
                throw new InvalidConfigException("Field 'validation' for textarea field '{$fieldId}' must be an array of rule strings.");
            }

            foreach ($fieldData['validation'] as $index => $rule) {
                if (!is_string($rule)) {
                    throw new InvalidConfigException("Validation rule at index {$index} for textarea field '{$fieldId}' must be a strict string.");
                }
                $validation[] = trim($rule);
            }
        }

        return array_merge($baseMeta, [
            'default'     => $default,
            'placeholder' => $placeholder,
            'comment'     => $comment,
            'class'       => $class,
            'validation'  => $validation,
        ]);
    }
}