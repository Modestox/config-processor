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
 * Validates table-like fields with dynamic rows.
 */
class DynamicRows extends AbstractFieldValidator
{
    /** @inheritDoc */
    protected function specificKeys(): array
    {
        return ['columns', 'default'];
    }

    /** @inheritDoc */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        if (!isset($fieldData['columns']) || !is_array($fieldData['columns'])) {
            throw new InvalidConfigException("Field 'columns' for dynamic rows field '{$fieldId}' must be a defined configuration array.");
        }

        $cleanColumns = [];
        foreach ($fieldData['columns'] as $colId => $colLabel) {
            if (!is_string($colId) || !is_string($colLabel)) {
                throw new InvalidConfigException("Column definitions for dynamic rows field '{$fieldId}' must be strict string key-value pairs.");
            }

            $cleanColId = trim($colId);
            if ($cleanColId === '') {
                throw new InvalidConfigException("Column identifier for dynamic rows field '{$fieldId}' cannot be empty.");
            }
            if (array_key_exists($cleanColId, $cleanColumns)) {
                throw new InvalidConfigException("Column '{$cleanColId}' is declared more than once in dynamic rows field '{$fieldId}'.");
            }

            $cleanColumns[$cleanColId] = trim($colLabel);
        }

        if ($cleanColumns === []) {
            throw new InvalidConfigException("Field 'columns' for dynamic rows field '{$fieldId}' must declare at least one column.");
        }

        $default = [];
        if (isset($fieldData['default'])) {
            if (!is_array($fieldData['default'])) {
                throw new InvalidConfigException("Field 'default' for dynamic rows field '{$fieldId}' must be an array of structural rows.");
            }

            foreach ($fieldData['default'] as $rowIndex => $rowValues) {
                if (!is_array($rowValues)) {
                    throw new InvalidConfigException("Row at index '{$rowIndex}' inside dynamic rows field '{$fieldId}' must be an array.");
                }

                foreach (array_keys($rowValues) as $cellKey) {
                    if (!array_key_exists((string)$cellKey, $cleanColumns)) {
                        throw new InvalidConfigException("Row at index '{$rowIndex}' inside dynamic rows field '{$fieldId}' contains unknown column '{$cellKey}'.");
                    }
                }

                $cleanRow = [];
                foreach ($cleanColumns as $colId => $colLabel) {
                    $cell = $rowValues[$colId] ?? null;

                    if ($cell !== null && !is_string($cell) && !is_int($cell) && !is_float($cell)) {
                        throw new InvalidConfigException("Cell '{$colId}' in row '{$rowIndex}' of dynamic rows field '{$fieldId}' must be a string or a number.");
                    }

                    // Missing cells are backfilled with an empty string to keep the row shape predictable.
                    $cleanRow[$colId] = $cell === null ? '' : trim((string)$cell);
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