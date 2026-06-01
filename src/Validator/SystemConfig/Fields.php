<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Validator\SystemConfig;

use Modestox\ConfigProcessor\Validator\ValidatorInterface;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\FieldValidatorInterface;
use Modestox\ConfigProcessor\Exception\InvalidConfigException;

use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Text;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Boolean;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Select;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Multiselect;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Radio;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Checkbox;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Textarea;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Number;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\YesNo;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Datetime;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Password;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\DynamicRows;

/**
 * Class Fields
 *
 * Coordinates multi-layered field validations using isolated type strategies.
 */
class Fields implements ValidatorInterface
{
    /**
     * @var array<string, FieldValidatorInterface>
     */
    private array $validators;

    /**
     * Fields constructor.
     * Injects type-specific validators.
     */
    public function __construct(array $validators = [])
    {
        $this->validators = $validators !== [] ? $validators : [
            'text'         => new Text(),
            'boolean'      => new Boolean(),
            'select'       => new Select(),
            'multiselect'  => new Multiselect(),
            'radio'        => new Radio(),
            'checkbox'     => new Checkbox(),
            'textarea'     => new Textarea(),
            'number'       => new Number(),
            'yes_no'       => new YesNo(),
            'datetime'     => new Datetime(),
            'password'     => new Password(),
            'dynamic_rows' => new DynamicRows(),

        ];
    }

    /**
     * @inheritDoc
     */
    public function validate(array $fields, array $context = []): array
    {
        $cleanFields = [];

        foreach ($fields as $fieldId => $fieldData) {
            if (!is_string($fieldId)) {
                throw new InvalidConfigException("The field identifier key must be a valid string.");
            }

            if (!is_array($fieldData)) {
                throw new InvalidConfigException("Configuration for field '{$fieldId}' must be an array.");
            }

            if (!isset($fieldData['type']) || !is_string($fieldData['type'])) {
                throw new InvalidConfigException("Field '{$fieldId}' must have a valid 'type' string parameter.");
            }

            $type = trim($fieldData['type']);

            if (!isset($this->validators[$type])) {
                $allowed = implode(', ', array_keys($this->validators));
                throw new InvalidConfigException("Unsupported type '{$type}' in field '{$fieldId}'. Supported: {$allowed}.");
            }

            // Centralized base metadata validation
            $baseMeta = [
                'type'       => $type,
                'label'      => $this->validateLabel($fieldId, $fieldData),
                'sort_order' => $this->validateSortOrder($fieldData, $fieldId),
            ];

            $cleanFields[$fieldId] = $this->validators[$type]->validate($fieldId, $fieldData, $baseMeta);
        }

        // Step 2: Cross-validation of 'depends' constraints strictly inside this group
        foreach ($fields as $fieldId => $fieldData) {
            if (isset($fieldData['depends'])) {
                if (!is_array($fieldData['depends'])) {
                    throw new InvalidConfigException("The 'depends' parameter for field '{$fieldId}' must be an array.");
                }

                if (count($fieldData['depends']) !== 1) {
                    throw new InvalidConfigException("Field '{$fieldId}' can only depend on exactly one parent field condition.");
                }

                $parentFieldId = (string)key($fieldData['depends']);
                $expectedValue = current($fieldData['depends']);

                if (!array_key_exists($parentFieldId, $cleanFields)) {
                    throw new InvalidConfigException(
                        "Field '{$fieldId}' depends on an undefined parent field '{$parentFieldId}' within the same group.",
                    );
                }

                if (!is_scalar($expectedValue)) {
                    throw new InvalidConfigException("The dependency target value for field '{$fieldId}' must be a strict scalar value.");
                }

                $cleanFields[$fieldId]['depends'] = [
                    $parentFieldId => $expectedValue,
                ];
            } else {
                $cleanFields[$fieldId]['depends'] = null;
            }
        }

        uasort($cleanFields, fn(array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);

        return $cleanFields;
    }

    private function validateLabel(string $fieldId, array $data): string
    {
        if (isset($data['label'])) {
            if (!is_string($data['label'])) {
                throw new InvalidConfigException("Field 'label' for '{$fieldId}' must be a strict string.");
            }
            return trim($data['label']) !== '' ? trim($data['label']) : $fieldId;
        }
        return $fieldId;
    }

    private function validateSortOrder(array $data, string $fieldId): int
    {
        if (isset($data['sort_order'])) {
            if (!is_int($data['sort_order'])) {
                throw new InvalidConfigException("Field 'sort_order' for '{$fieldId}' must be a strict integer.");
            }
            return $data['sort_order'];
        }
        return 0;
    }
}