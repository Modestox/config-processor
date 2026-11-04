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
use DateTimeImmutable;

/**
 * Class Datetime
 *
 * Validates date and time fields.
 */
class Datetime extends AbstractFieldValidator
{
    /**
     * Supported formats with their regex rules.
     */
    private const FORMATS = [
        'datetime' => ['format' => 'Y-m-d H:i:s', 'regex' => '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/'],
        'date'     => ['format' => 'Y-m-d',       'regex' => '/^\d{4}-\d{2}-\d{2}$/'],
        'time'     => ['format' => 'H:i:s',       'regex' => '/^\d{2}:\d{2}:\d{2}$/'],
    ];

    /** @inheritDoc */
    protected function specificKeys(): array
    {
        return ['view_mode', 'default'];
    }

    /** @inheritDoc */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        $subType = 'datetime';
        if (isset($fieldData['view_mode'])) {
            if (!is_string($fieldData['view_mode'])) {
                throw new InvalidConfigException("Field 'view_mode' for temporal field '{$fieldId}' must be a strict string.");
            }

            $requestedMode = trim($fieldData['view_mode']);
            if (!array_key_exists($requestedMode, self::FORMATS)) {
                $allowed = implode(', ', array_keys(self::FORMATS));
                throw new InvalidConfigException("Unsupported view_mode '{$requestedMode}' in field '{$fieldId}'. Supported: {$allowed}.");
            }
            $subType = $requestedMode;
        }

        $config = self::FORMATS[$subType];

        $default = '';
        if (isset($fieldData['default'])) {
            if (!is_string($fieldData['default'])) {
                throw new InvalidConfigException("Default value for temporal field '{$fieldId}' must be a strict string.");
            }

            $defaultVal = trim($fieldData['default']);

            if ($defaultVal !== '' && !$this->isValidTimestamp($defaultVal, $config['format'], $config['regex'])) {
                throw new InvalidConfigException(
                    "Default value '{$defaultVal}' for field '{$fieldId}' does not match mandatory '{$subType}' format standard ('{$config['format']}')."
                );
            }
            $default = $defaultVal;
        }

        $specific = [
            'view_mode' => $subType,
            'default'   => $default,
        ];

        return array_merge($baseMeta, $specific, $this->validateCommon($fieldId, $fieldData));
    }

    /**
     * Checks format and calendar validity.
     */
    private function isValidTimestamp(string $value, string $format, string $regex): bool
    {
        if (!preg_match($regex, $value)) {
            return false;
        }

        $dateTime = DateTimeImmutable::createFromFormat($format, $value);
        return $dateTime !== false && $dateTime->format($format) === $value;
    }
}