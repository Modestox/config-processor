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
 * Class Boolean
 *
 * Validates and normalizes attributes specific only to the 'boolean' toggle/checkbox field type.
 */
class Boolean implements FieldValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        // 1. Validate boolean-specific attribute: 'default' (Must be a strict bool)
        $default = false;
        if (isset($fieldData['default'])) {
            if (!is_bool($fieldData['default'])) {
                throw new InvalidConfigException("Field 'default' for boolean field '{$fieldId}' must be a strict boolean value.");
            }
            $default = $fieldData['default'];
        }

        // 2. Validate UI modifier: 'comment'
        $comment = '';
        if (isset($fieldData['comment'])) {
            if (!is_string($fieldData['comment'])) {
                throw new InvalidConfigException("Field 'comment' for boolean field '{$fieldId}' must be a strict string.");
            }
            $comment = trim($fieldData['comment']);
        }

        // 3. Validate CSS layout modifier: 'class'
        $class = '';
        if (isset($fieldData['class'])) {
            if (!is_string($fieldData['class'])) {
                throw new InvalidConfigException("Field 'class' for boolean field '{$fieldId}' must be a valid string.");
            }
            $class = trim($fieldData['class']);
        }

        return array_merge($baseMeta, [
            'default' => $default,
            'comment' => $comment,
            'class'   => $class,
        ]);
    }
}