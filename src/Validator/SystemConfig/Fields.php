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

use InvalidArgumentException;
use Modestox\ConfigProcessor\Exception\InvalidConfigException;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Boolean;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Checkbox;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Datetime;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\DynamicRows;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\FieldValidatorInterface;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\File;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Image;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\InfoBlock;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Multiselect;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Number;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Password;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Radio;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Select;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Text;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Textarea;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\YesNo;

/**
 * Class Fields
 *
 * Validates fields and resolves type strategies and dependencies.
 */
class Fields extends AbstractNodeValidator
{
    private const TYPE_PATTERN = '/^[a-z0-9_]+$/D';

    /** Field types whose value is one of the declared options. */
    private const OPTION_TYPES = ['select', 'radio', 'yes_no'];

    /** @var array<string, FieldValidatorInterface> */
    private array $validators;

    /**
     * Creates the validator.
     *
     * @param array<string, FieldValidatorInterface> $validators Custom validators extending or overriding defaults.
     * @throws InvalidArgumentException
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
            if (!is_string($type) || !$validator instanceof FieldValidatorInterface) {
                throw new InvalidArgumentException(
                    'Custom validators must be passed as a map of type name => ' . FieldValidatorInterface::class . '.',
                );
            }
            $this->registerType($type, $validator);
        }
    }

    /**
     * Registers or overrides a field type validator.
     *
     * @throws InvalidArgumentException
     */
    public function registerType(string $type, FieldValidatorInterface $validator): void
    {
        $type = trim($type);

        if (!preg_match(self::TYPE_PATTERN, $type)) {
            throw new InvalidArgumentException(
                "The field type name '{$type}' is invalid. Only lower-case alphanumeric characters and underscores are allowed (a-z, 0-9, _).",
            );
        }

        $this->validators[$type] = $validator;
    }

    /** @inheritDoc */
    protected function entity(): string
    {
        return 'field';
    }

    /** @inheritDoc */
    protected function validateNode(string $id, array $data, array $context): array
    {
        if (!isset($data['type']) || !is_string($data['type'])) {
            throw new InvalidConfigException("Field '{$id}' must have a valid 'type' string parameter.");
        }

        $type = trim($data['type']);

        if (!isset($this->validators[$type])) {
            $allowed = implode(', ', array_keys($this->validators));
            throw new InvalidConfigException("Unsupported type '{$type}' in field '{$id}'. Supported: {$allowed}.");
        }

        $validator = $this->validators[$type];

        $this->assertKnownKeys($data, $validator->allowedKeys(), $id);

        $baseMeta = [
            'type'       => $type,
            'label'      => $this->validateLabel($data, $id),
            'sort_order' => $this->validateSortOrder($data, $id),
        ];

        $clean = $validator->validate($id, $data, $baseMeta);
        $clean['depends'] = $this->validateDependsShape($data, $id);

        return $clean;
    }

    /**
     * Validates the shape of 'depends'; parents are checked later.
     *
     * @param array<string, mixed> $data
     * @return array<string, scalar>|null
     * @throws InvalidConfigException
     */
    private function validateDependsShape(array $data, string $id): ?array
    {
        if (!isset($data['depends'])) {
            return null;
        }
        if (!is_array($data['depends'])) {
            throw new InvalidConfigException("The 'depends' parameter for field '{$id}' must be an array.");
        }
        if ($data['depends'] === []) {
            throw new InvalidConfigException("The 'depends' array for field '{$id}' cannot be empty.");
        }

        $depends = [];
        foreach ($data['depends'] as $parentId => $expected) {
            if (!is_scalar($expected)) {
                throw new InvalidConfigException("The dependency target value for field '{$id}' must be a strict scalar value.");
            }
            $depends[(string)$parentId] = $expected;
        }

        return $depends;
    }

    /**
     * @inheritDoc
     *
     * Checks parents, expected values and cycles of 'depends'.
     */
    protected function validateRelations(array $nodes): array
    {
        $graph = [];

        foreach ($nodes as $fieldId => $field) {
            if ($field['depends'] === null) {
                continue;
            }

            try {
                foreach ($field['depends'] as $parentId => $expected) {
                    $parentId = (string)$parentId;

                    if (!array_key_exists($parentId, $nodes)) {
                        throw new InvalidConfigException(
                            "Field '{$fieldId}' depends on an undefined parent field '{$parentId}' within the same group.",
                        );
                    }

                    $this->assertExpectedValue($fieldId, $parentId, $nodes[$parentId], $expected);
                }
            } catch (InvalidConfigException $e) {
                throw $e->prependPath((string)$fieldId);
            }

            $graph[$fieldId] = array_map('strval', array_keys($field['depends']));
        }

        $this->assertNoCircularDependencies($graph);

        return $nodes;
    }

    /**
     * Checks that the expected value makes sense for the parent field.
     *
     * @param array<string, mixed> $parent Clean parent field.
     * @throws InvalidConfigException
     */
    private function assertExpectedValue(string $fieldId, string $parentId, array $parent, mixed $expected): void
    {
        $type = $parent['type'];
        $shown = json_encode($expected);

        if ($type === 'infoblock') {
            throw new InvalidConfigException("Field '{$fieldId}' cannot depend on infoblock '{$parentId}': it holds no value.");
        }

        if (in_array($type, self::OPTION_TYPES, true) && is_array($parent['options'] ?? null)) {
            $isKey = (is_int($expected) || is_string($expected)) && array_key_exists($expected, $parent['options']);
            if (!$isKey) {
                $options = implode(', ', array_keys($parent['options']));
                throw new InvalidConfigException(
                    "Field '{$fieldId}' expects the value {$shown} from '{$parentId}', which is not one of its options ({$options}).",
                );
            }
            return;
        }

        if ($type === 'boolean' && !is_bool($expected)) {
            throw new InvalidConfigException(
                "Field '{$fieldId}' expects the value {$shown} from boolean field '{$parentId}'; use true or false.",
            );
        }

        if ($type === 'number' && !is_int($expected) && !is_float($expected)) {
            throw new InvalidConfigException(
                "Field '{$fieldId}' expects the value {$shown} from number field '{$parentId}'; use an integer or a float.",
            );
        }
    }

    /**
     * Rejects self-dependencies and dependency cycles.
     *
     * @param array<string, list<string>> $graph Field id => parent field ids.
     * @throws InvalidConfigException
     */
    private function assertNoCircularDependencies(array $graph): void
    {
        $state = [];

        foreach (array_keys($graph) as $fieldId) {
            $this->visitDependency((string)$fieldId, $graph, $state, []);
        }
    }

    /**
     * Depth-first walk over the dependency graph.
     *
     * @param array<string, list<string>> $graph
     * @param array<string, int> $state 1 = in progress, 2 = done.
     * @param list<string> $path
     * @throws InvalidConfigException
     */
    private function visitDependency(string $fieldId, array $graph, array &$state, array $path): void
    {
        $current = $state[$fieldId] ?? 0;

        if ($current === 2) {
            return;
        }

        if ($current === 1) {
            $cycle = array_slice($path, (int)array_search($fieldId, $path, true));
            $cycle[] = $fieldId;
            throw new InvalidConfigException('Circular dependency detected between fields: ' . implode(' -> ', $cycle) . '.');
        }

        $state[$fieldId] = 1;
        $path[] = $fieldId;

        foreach ($graph[$fieldId] ?? [] as $parentFieldId) {
            $this->visitDependency($parentFieldId, $graph, $state, $path);
        }

        $state[$fieldId] = 2;
    }
}