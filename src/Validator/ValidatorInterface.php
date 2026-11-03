<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Validator;

use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Interface ValidatorInterface
 *
 * Contract for configuration level validators.
 */
interface ValidatorInterface
{
    /**
     * Validates and normalizes a configuration level.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     * @throws InvalidConfigException
     */
    public function validate(array $data, array $context = []): array;
}