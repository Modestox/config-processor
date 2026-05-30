<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Merger;

use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Class ConfigMerger
 *
 * Safely merges multiple multi-dimensional configuration arrays from different modules,
 * detecting structural identifier collisions before validation.
 */
class ConfigMerger
{
    /**
     * Merges a collection of raw configuration arrays into one unified structure.
     *
     * @param array<int, array<string, mixed>> $configs List of configuration arrays from modules.
     * @return array<string, mixed> Unified merged configuration array.
     * @throws InvalidConfigException If a critical identifier collision or parameter overwrite is detected.
     */
    public function merge(array $configs): array
    {
        $masterConfig = [];

        foreach ($configs as $index => $config) {
            if (!is_array($config)) {
                throw new InvalidConfigException("Configuration at index {$index} must be an array.");
            }

            $masterConfig = $this->mergeRecursiveDistinct($masterConfig, $config);
        }

        return $masterConfig;
    }

    /**
     * Deeply merges two configuration arrays with strict collision restrictions.
     *
     * @param array<string, mixed> $array1
     * @param array<string, mixed> $array2
     * @return array<string, mixed>
     * @throws InvalidConfigException
     */
    private function mergeRecursiveDistinct(array $array1, array $array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            // If the key is an integer, it violates our requirement for strict string identifiers
            if (!is_string($key)) {
                throw new InvalidConfigException("Configuration keys must be strict strings. Integer key detected.");
            }

            if (isset($merged[$key])) {
                if (is_array($merged[$key]) && is_array($value)) {
                    // Node exists in both and both are arrays (e.g., 'tabs', 'sections', 'groups', 'fields')
                    // We go deeper down the cascading rabbit hole
                    $merged[$key] = $this->mergeRecursiveDistinct($merged[$key], $value);
                } else {
                    // Scalar conflict or structural type mismatch (e.g., two modules trying to overwrite 'label' or 'sort_order')
                    if ($merged[$key] !== $value) {
                        throw new InvalidConfigException(
                            sprintf("Configuration collision detected at key '%s'. Value mismatch between modules.", $key),
                        );
                    }
                }
            } else {
                // Key is unique, safely adopt the new configuration branch
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}