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
 * Class Select
 *
 * Validates select fields.
 */
class Select extends AbstractFieldValidator
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
        $type = (string)($baseMeta['type'] ?? 'select');
        $options = $this->validateOptions($fieldData, $fieldId, $type);

        // Without an explicit default the first option is selected.
        $default = array_key_first($options);

        if (isset($fieldData['default'])) {
            $requested = $fieldData['default'];

            if (!is_string($requested) && !is_int($requested)) {
                throw new InvalidConfigException("Field 'default' for {$type} field '{$fieldId}' must be a strict string or integer.");
            }
            if (!array_key_exists($requested, $options)) {
                throw new InvalidConfigException(
                    "The default value '{$requested}' for field '{$fieldId}' does not exist within the permitted options scope.",
                );
            }

            // PHP turns numeric string keys into integers; the default follows the same rule.
            $default = array_key_first([$requested => true]);
        }

        return array_merge($baseMeta, [
            'options' => $options,
            'default' => $default,
        ], $this->validateCommon($fieldId, $fieldData));
    }
}