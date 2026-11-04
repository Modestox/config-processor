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
use Modestox\ConfigProcessor\Validator\Identifier;
use Modestox\ConfigProcessor\Validator\Keys;
use Modestox\ConfigProcessor\Validator\ValidatorInterface;

/**
 * Class AbstractNodeValidator
 *
 * Shared logic of tabs, sections, groups and fields validators.
 */
abstract class AbstractNodeValidator implements ValidatorInterface
{
    /**
     * Entity name used in messages, e.g. 'tab'.
     */
    abstract protected function entity(): string;

    /**
     * Validates one node and returns its clean form.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     * @throws InvalidConfigException
     */
    abstract protected function validateNode(string $id, array $data, array $context): array;

    /**
     * @inheritDoc
     */
    public function validate(array $data, array $context = []): array
    {
        $clean = [];

        foreach ($data as $id => $nodeData) {
            $id = Identifier::assert($id, $this->entity());

            try {
                if (!is_array($nodeData)) {
                    throw new InvalidConfigException("Configuration for {$this->entity()} '{$id}' must be an array.");
                }

                $clean[$id] = array_merge($this->validateNode($id, $nodeData, $context), Keys::extensions($nodeData));
            } catch (InvalidConfigException $e) {
                throw $e->prependPath($id);
            }
        }

        $clean = $this->validateRelations($clean);

        uasort($clean, static fn(array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);

        return $clean;
    }

    /**
     * Validates links between sibling nodes after all of them are clean.
     *
     * @param array<string, array<string, mixed>> $nodes
     * @return array<string, array<string, mixed>>
     * @throws InvalidConfigException
     */
    protected function validateRelations(array $nodes): array
    {
        return $nodes;
    }

    /**
     * Rejects unknown keys of a node.
     *
     * @param array<array-key, mixed> $data
     * @param list<string> $allowed
     * @throws InvalidConfigException
     */
    protected function assertKnownKeys(array $data, array $allowed, string $id): void
    {
        Keys::assertKnown($data, $allowed, "{$this->entity()} '{$id}'");
    }

    /**
     * Validates the label, falling back to the id.
     *
     * @param array<string, mixed> $data
     * @throws InvalidConfigException
     */
    protected function validateLabel(array $data, string $id): string
    {
        if (!isset($data['label'])) {
            return $id;
        }
        if (!is_string($data['label'])) {
            throw new InvalidConfigException("Field 'label' for {$this->entity()} '{$id}' must be a strict string.");
        }

        $label = trim($data['label']);

        return $label !== '' ? $label : $id;
    }

    /**
     * Validates the sort order, falling back to 0.
     *
     * @param array<string, mixed> $data
     * @throws InvalidConfigException
     */
    protected function validateSortOrder(array $data, string $id): int
    {
        if (!isset($data['sort_order'])) {
            return 0;
        }
        if (!is_int($data['sort_order'])) {
            throw new InvalidConfigException("Field 'sort_order' for {$this->entity()} '{$id}' must be a strict integer.");
        }

        return $data['sort_order'];
    }

    /**
     * Validates an optional string, falling back to ''.
     *
     * @param array<string, mixed> $data
     * @throws InvalidConfigException
     */
    protected function validateOptionalString(array $data, string $key, string $id, string $expected = 'a valid string'): string
    {
        if (!isset($data[$key])) {
            return '';
        }
        if (!is_string($data[$key])) {
            throw new InvalidConfigException("Field '{$key}' for {$this->entity()} '{$id}' must be {$expected}.");
        }

        return trim($data[$key]);
    }
}