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
 * Validates temporal fields (date, time, or datetime combination) using strict ISO/24h standards.
 */
class Datetime extends AbstractFieldValidator
{
    /**
     * Supported internal formats mapped to validation regex rules.
     */
    private const FORMATS = [
        'datetime' => ['format' => 'Y-m-d H:i:s', 'regex' => '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/'],
        'date'     => ['format' => 'Y-m-d',       'regex' => '/^\d{4}-\d{2}-\d{2}$/'],
        'time'     => ['format' => 'H:i:s',       'regex' => '/^\d{2}:\d{2}:\d{2}$/'],
    ];

    /**
     * @inheritDoc
     */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        // 1. Determine temporal subtype (defaulting to combined datetime)
        $subType = 'datetime';
        if (isset($fieldData['view_mode']) && is_string($fieldData['view_mode'])) {
            $requestedMode = trim($fieldData['view_mode']);
            if (array_key_exists($requestedMode, self::FORMATS)) {
                $subType = $requestedMode;
            }
        }

        $config = self::FORMATS[$subType];

        // 2. Validate 'default' parameter using strict format matching
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
     * Enforces strict logical timestamp verification.
     */
    private function isValidTimestamp(string $value, string $format, string $regex): bool
    {
        if (!preg_match($regex, $value)) {
            return false;
        }

        $dateTime = DateTimeImmutable::createFromFormat($format, $value);
        return $dateTime && $dateTime->format($format) === $value;
    }
}