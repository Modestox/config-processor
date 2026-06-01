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
 * Class DynamicRows
 *
 * Validates table-like configuration structures where administrators can add dynamic rows.
 */
class DynamicRows extends AbstractFieldValidator
{
    /**
     * @inheritDoc
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        // 1. Enforce columns definition structure
        if (!isset($fieldData['columns']) || !is_array($fieldData['columns'])) {
            throw new InvalidConfigException("Field 'columns' for dynamic rows field '{$fieldId}' must be a defined configuration array.");
        }

        $cleanColumns = [];
        foreach ($fieldData['columns'] as $colId => $colLabel) {
            if (!is_string($colId) || !is_string($colLabel)) {
                throw new InvalidConfigException("Column definitions for dynamic rows field '{$fieldId}' must be strict string key-value pairs.");
            }
            $cleanColumns[trim($colId)] = trim($colLabel);
        }

        // 2. Validate 'default' rows payload structure
        $default = [];
        if (isset($fieldData['default'])) {
            if (!is_array($fieldData['default'])) {
                throw new InvalidConfigException("Field 'default' for dynamic rows field '{$fieldId}' must be an array of structural rows.");
            }

            foreach ($fieldData['default'] as $rowIndex => $rowValues) {
                if (!is_array($rowValues)) {
                    throw new InvalidConfigException("Row at index '{$rowIndex}' inside dynamic rows field '{$fieldId}' must be an array.");
                }

                $cleanRow = [];
                foreach ($cleanColumns as $colId => $colLabel) {
                    // Fill with empty strings if a column missing in a default row, keeping structure predictable
                    $cleanRow[$colId] = isset($rowValues[$colId]) ? trim((string)$rowValues[$colId]) : '';
                }
                $default[$rowIndex] = $cleanRow;
            }
        }

        $specific = [
            'columns' => $cleanColumns,
            'default' => $default,
        ];

        return array_merge($baseMeta, $specific, $this->validateCommon($fieldId, $fieldData));
    }
}