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

use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Class Groups
 *
 * Validates groups and delegates fields validation.
 */
class Groups extends AbstractNodeValidator
{
    private readonly Fields $fieldsValidator;

    /**
     * Creates the validator.
     */
    public function __construct(?Fields $fieldsValidator = null)
    {
        $this->fieldsValidator = $fieldsValidator ?? new Fields();
    }

    /** @inheritDoc */
    protected function entity(): string
    {
        return 'group';
    }

    /** @inheritDoc */
    protected function validateNode(string $id, array $data, array $context): array
    {
        $this->assertKnownKeys($data, ['label', 'sort_order', 'fields'], $id);

        $label = $this->validateLabel($data, $id);
        $sortOrder = $this->validateSortOrder($data, $id);

        $fields = [];
        if (isset($data['fields'])) {
            if (!is_array($data['fields'])) {
                throw new InvalidConfigException("The 'fields' under group '{$id}' must be an array.");
            }

            try {
                $fields = $this->fieldsValidator->validate($data['fields']);
            } catch (InvalidConfigException $e) {
                throw $e->prependPath('fields');
            }
        }

        return [
            'label'      => $label,
            'sort_order' => $sortOrder,
            'fields'     => $fields,
        ];
    }
}