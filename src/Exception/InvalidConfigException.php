<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Exception;

/**
 * Class InvalidConfigException
 *
 * Standard library domain exception thrown when a configuration structure
 * or field type validation rule is violated.
 */
class InvalidConfigException extends \Exception
{
    // Pure standard PHP exception without rendering or side-effects
}