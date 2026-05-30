<?php

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Validator;

/**
 * Interface ValidatorInterface
 *
 * Defines the contract for all sub-schema processors within the CMS ecosystem.
 */
interface ValidatorInterface
{
    /**
     * Validates and sanitizes the tabs list with fallback rules.
     *
     * @param array $data
     * @param array $context
     * @return array<string, mixed>
     * @throws InvalidConfigException
     */
    public function validate(array $data, array $context = []): array;
}