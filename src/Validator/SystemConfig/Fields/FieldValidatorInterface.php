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
 * Interface FieldValidatorInterface
 *
 * Defines the contract for specific field type validation strategies.
 */
interface FieldValidatorInterface
{
    /**
     * Validates type-specific attributes and merges them with base metadata.
     *
     * @param string $fieldId The unique identifier of the field.
     * @param array<string, mixed> $fieldData Raw configuration input data for the field.
     * @param array<string, mixed> $baseMeta Already verified shared metadata (type, label, sort_order).
     * @return array<string, mixed> Complete sanitized field configuration matrix.
     * @throws InvalidConfigException If any type-specific validation rule is violated.
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array;
}