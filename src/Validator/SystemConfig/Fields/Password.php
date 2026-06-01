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

/**
 * Class Password
 *
 * Validates and normalizes password or sensitive API key inputs, masking them by default.
 */
class Password extends AbstractFieldValidator
{
    /**
     * @inheritDoc
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        // Passwords must be strict strings. We trim them to prevent accidental copy-paste spaces.
        $specific = [
            'default' => $this->validateString($fieldData, 'default', $fieldId, ''),
        ];

        // Merge base meta, specific attributes, and shared common fields (comment, class, required)
        return array_merge($baseMeta, $specific, $this->validateCommon($fieldId, $fieldData));
    }
}