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
 * Class Text
 *
 * Validates and normalizes attributes specific only to the standard 'text' input field type.
 */
class Text implements FieldValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        // 1. Validate text-specific UI attribute: 'default'
        $default = '';
        if (isset($fieldData['default'])) {
            if (!is_string($fieldData['default'])) {
                throw new InvalidConfigException("Field 'default' for text field '{$fieldId}' must be a strict string.");
            }
            $default = $fieldData['default'];
        }

        // 2. Validate text-specific UI attribute: 'placeholder'
        $placeholder = '';
        if (isset($fieldData['placeholder'])) {
            if (!is_string($fieldData['placeholder'])) {
                throw new InvalidConfigException("Field 'placeholder' for text field '{$fieldId}' must be a strict string.");
            }
            $placeholder = trim($fieldData['placeholder']);
        }

        // 3. Validate text-specific UI attribute: 'comment' (Replaced 'description')
        $comment = '';
        if (isset($fieldData['comment'])) {
            if (!is_string($fieldData['comment'])) {
                throw new InvalidConfigException("Field 'comment' for text field '{$fieldId}' must be a strict string.");
            }
            $comment = trim($fieldData['comment']);
        }

        // 4. Validate CSS layout modifier attribute: 'class'
        $class = '';
        if (isset($fieldData['class'])) {
            if (!is_string($fieldData['class'])) {
                throw new InvalidConfigException("Field 'class' for text field '{$fieldId}' must be a valid string.");
            }
            $class = trim($fieldData['class']);
        }

        // 5. Validate input rules collection: 'validation'
        $validation = [];
        if (isset($fieldData['validation'])) {
            if (!is_array($fieldData['validation'])) {
                throw new InvalidConfigException("Field 'validation' for text field '{$fieldId}' must be an array of rule strings.");
            }

            foreach ($fieldData['validation'] as $index => $rule) {
                if (!is_string($rule)) {
                    throw new InvalidConfigException("Validation rule at index {$index} for text field '{$fieldId}' must be a strict string.");
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