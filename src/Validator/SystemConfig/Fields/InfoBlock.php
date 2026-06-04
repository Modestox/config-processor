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
 * Class InfoBlock
 *
 * Validates non-data UI notification blocks used for displaying contextual instructions
 * or warnings with extensible format standards.
 */
class InfoBlock extends AbstractFieldValidator
{
    private const ALLOWED_FORMATS = ['text', 'html'];

    /**
     * @inheritDoc
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        // 1. Enforce strict requirements for the mandatory payload description text
        if (!isset($fieldData['text'])) {
            throw new InvalidConfigException("Field 'text' is mandatory for infoblock type in '{$fieldId}'.");
        }
        if (!is_string($fieldData['text'])) {
            throw new InvalidConfigException("Field 'text' for infoblock '{$fieldId}' must be a strict string.");
        }

        // 2. Validate and scale the format parameter preventing future API breaks
        $format = 'text';
        if (isset($fieldData['format'])) {
            if (!is_string($fieldData['format'])) {
                throw new InvalidConfigException("Field 'format' for infoblock '{$fieldId}' must be a strict string.");
            }
            $requestedFormat = trim($fieldData['format']);
            if (!in_array($requestedFormat, self::ALLOWED_FORMATS, true)) {
                $allowed = implode(', ', self::ALLOWED_FORMATS);
                throw new InvalidConfigException("Unsupported format '{$requestedFormat}' in infoblock '{$fieldId}'. Supported: {$allowed}.");
            }
            $format = $requestedFormat;
        }

        $specific = [
            'text'   => trim($fieldData['text']),
            'format' => $format,
        ];

        // Deep merge including abstract generic attributes (sort_order, label, class, comment, required, validation)
        return array_merge($baseMeta, $specific, $this->validateCommon($fieldId, $fieldData));
    }
}