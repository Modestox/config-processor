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

/**
 * Class Radio
 *
 * Validates radio button group structures by inheriting standard select list constraints.
 */
class Radio extends Select
{
    // Inherits all validation rules and 'validateCommon' integration from Select
}