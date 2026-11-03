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
 * Contract for field type validators.
 */
interface FieldValidatorInterface
{
    /**
     * Validates type-specific attributes.
     *
     * @param array<string, mixed> $fieldData Raw field configuration.
     * @param array<string, mixed> $baseMeta Verified shared metadata (type, label, sort_order).
     * @return array<string, mixed>
     * @throws InvalidConfigException
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array;
}