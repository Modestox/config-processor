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
 * Class Boolean
 *
 * Validates boolean fields.
 */
class Boolean extends AbstractFieldValidator
{
    /** @inheritDoc */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        $specific = [
            'default' => $this->validateBool($fieldData, 'default', $fieldId, false),
        ];
        return array_merge($baseMeta, $specific, $this->validateCommon($fieldId, $fieldData));
    }
}