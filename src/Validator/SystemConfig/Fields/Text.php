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
 * Class Text
 *
 * Validates text fields.
 */
class Text extends AbstractFieldValidator
{
    /** @inheritDoc */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        $specific = [
            'default'     => $this->validateString($fieldData, 'default', $fieldId, ''),
            'placeholder' => $this->validateString($fieldData, 'placeholder', $fieldId, ''),
        ];
        return array_merge($baseMeta, $specific, $this->validateCommon($fieldId, $fieldData));
    }
}