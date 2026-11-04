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
 * Class Multiselect
 *
 * Validates multiselect fields.
 */
class Multiselect extends AbstractFieldValidator
{
    /** @inheritDoc */
    protected function specificKeys(): array
    {
        return ['options', 'default'];
    }

    /**
     * @inheritDoc
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        $type = (string)($baseMeta['type'] ?? 'multiselect');
        $options = $this->validateOptions($fieldData, $fieldId, $type);

        $default = [];

        if (isset($fieldData['default'])) {
            if (!is_array($fieldData['default'])) {
                throw new InvalidConfigException("Field 'default' for {$type} field '{$fieldId}' must be a strict array of keys.");
            }

            $seen = [];
            foreach ($fieldData['default'] as $index => $key) {
                if (!is_string($key) && !is_int($key)) {
                    throw new InvalidConfigException(
                        "Default value at index {$index} for {$type} field '{$fieldId}' must be a strict string or integer.",
                    );
                }
                if (!array_key_exists($key, $options)) {
                    throw new InvalidConfigException(
                        "The default value '{$key}' for field '{$fieldId}' does not exist within the permitted options scope.",
                    );
                }

                // PHP turns numeric string keys into integers; '1' and 1 are the same option.
                $normalized = array_key_first([$key => true]);
                if (array_key_exists($normalized, $seen)) {
                    throw new InvalidConfigException("The default value '{$key}' for field '{$fieldId}' is listed more than once.");
                }

                $seen[$normalized] = true;
                $default[] = $normalized;
            }
        }

        return array_merge($baseMeta, [
            'options' => $options,
            'default' => $default,
        ], $this->validateCommon($fieldId, $fieldData));
    }
}