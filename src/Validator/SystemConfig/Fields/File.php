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
 * Class File
 *
 * Validates and normalizes declarative rules for standard file uploads.
 */
class File extends AbstractFieldValidator
{
    /**
     * @inheritDoc
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        $specific = [
            'upload_dir' => $this->validateString($fieldData, 'upload_dir', $fieldId, ''),
            'extensions' => $this->validateString($fieldData, 'extensions', $fieldId, ''),
        ];

        return array_merge($baseMeta, $specific, $this->validateCommon($fieldId, $fieldData));
    }
}