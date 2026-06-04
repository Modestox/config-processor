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
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\File;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Image;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\InfoBlock;

class Fields implements ValidatorInterface
{
    /** @var array<string, FieldValidatorInterface> */
    private array $validators = [];

    /**
     * Fields constructor.
     *
     * @param array<string, FieldValidatorInterface> $validators Custom validators to extend or override defaults.
     */
    public function __construct(array $validators = [])
    {
        $this->validators = [
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
            'file'         => new File(),
            'image'        => new Image(),
            'infoblock'    => new InfoBlock(),
        ];

        foreach ($validators as $type => $validator) {
            if (is_string($type) && $validator instanceof FieldValidatorInterface) {
                $this->registerType($type, $validator);
            }
        }
    }

    /**
     * Dynamically extends the component with a custom field type validator strategy.
     */
    public function registerType(string $type, FieldValidatorInterface $validator): void
    {
        $this->validators[trim($type)] = $validator;
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

            $baseMeta = [
                'type'       => $type,
                'label'      => $this->validateLabel($fieldId, $fieldData),
                'sort_order' => $this->validateSortOrder($fieldData, $fieldId),
            ];

            $cleanFields[$fieldId] = $this->validators[$type]->validate($fieldId, $fieldData, $baseMeta);
        }

        // Step 2: Cross-validation of MULTIPLE 'depends' constraints (Enterprise ready)
        foreach ($fields as $fieldId => $fieldData) {
            if (isset($fieldData['depends'])) {
                if (!is_array($fieldData['depends'])) {
                    throw new InvalidConfigException("The 'depends' parameter for field '{$fieldId}' must be an array.");
                }
                if ($fieldData['depends'] === []) {
                    throw new InvalidConfigException("The 'depends' array for field '{$fieldId}' cannot be empty.");
                }

                $cleanDepends = [];
                foreach ($fieldData['depends'] as $parentFieldId => $expectedValue) {
                    $parentFieldId = (string)$parentFieldId;

                    if (!array_key_exists($parentFieldId, $cleanFields)) {
                        throw new InvalidConfigException(
                            "Field '{$fieldId}' depends on an undefined parent field '{$parentFieldId}' within the same group.",
                        );
                    }
                    if (!is_scalar($expectedValue)) {
                        throw new InvalidConfigException("The dependency target value for field '{$fieldId}' must be a strict scalar value.");
                    }

                    $cleanDepends[$parentFieldId] = $expectedValue;
                }

                $cleanFields[$fieldId]['depends'] = $cleanDepends;
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