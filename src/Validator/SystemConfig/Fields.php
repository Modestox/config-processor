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
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Text;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Boolean;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Select;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Multiselect;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Radio;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Checkbox;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Textarea;
use Modestox\ConfigProcessor\Exception\InvalidConfigException;

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
     * Injects type-specific validators using standard DI patterns.
     */
    public function __construct(array $validators = [])
    {
        // Fallback initialization for out-of-the-box usage, keeping DI container compatible
        $this->validators = $validators !== [] ? $validators : [
            'text'        => new Text(),
            'boolean'     => new Boolean(),
            'select'      => new Select(),
            'multiselect' => new Multiselect(),
            'radio'       => new Radio(),
            'checkbox'    => new Checkbox(),
            'textarea'    => new Textarea(),
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

            if (!isset($fieldData['type'])) {
                throw new InvalidConfigException("Field '{$fieldId}' is missing its mandatory 'type' parameter.");
            }

            if (!is_string($fieldData['type'])) {
                throw new InvalidConfigException("Field 'type' for field '{$fieldId}' must be a strict string.");
            }

            $type = trim($fieldData['type']);

            // Validate common metadata 'label' for ALL fields
            $label = $fieldId;
            if (isset($fieldData['label'])) {
                if (!is_string($fieldData['label'])) {
                    throw new InvalidConfigException("Field 'label' for field '{$fieldId}' must be a strict string.");
                }
                $trimmedLabel = trim($fieldData['label']);
                if ($trimmedLabel !== '') {
                    $label = $trimmedLabel;
                }
            }

            // Validate common metadata 'sort_order' for ALL fields
            $sortOrder = 0;
            if (isset($fieldData['sort_order'])) {
                if (!is_int($fieldData['sort_order'])) {
                    throw new InvalidConfigException("Field 'sort_order' for field '{$fieldId}' must be a strict integer.");
                }
                $sortOrder = $fieldData['sort_order'];
            }

            $baseMeta = [
                'type'       => $type,
                'label'      => $label,
                'sort_order' => $sortOrder,
            ];

            // Delegate validation to the specific registered type validator strategy
            if (!isset($this->validators[$type])) {
                $allowedTypes = implode(', ', array_keys($this->validators));
                throw new InvalidConfigException(
                    "Field '{$fieldId}' uses an unsupported type '{$type}'. Supported types are: {$allowedTypes}.",
                );
            }

            $cleanFields[$fieldId] = $this->validators[$type]->validate($fieldId, $fieldData, $baseMeta);
        }

        uasort($cleanFields, fn(array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);

        return $cleanFields;
    }
}