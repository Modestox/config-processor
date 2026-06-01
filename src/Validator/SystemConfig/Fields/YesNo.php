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
 * Class YesNo
 *
 * Automatically generates standard binary options (0 => No, 1 => Yes) to simplify layout definitions.
 */
class YesNo extends Select
{
    /**
     * @inheritDoc
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        // Automatically inject hardcoded options for developer convenience and speed
        $fieldData['options'] = [
            0 => 'No',
            1 => 'Yes',
        ];

        // Fallback to 0 if default is not specified or invalid
        if (!isset($fieldData['default'])) {
            $fieldData['default'] = 0;
        }

        // Delegate to the parent Select validator, which will seamlessly process the injected options
        return parent::validate($fieldId, $fieldData, $baseMeta);
    }
}