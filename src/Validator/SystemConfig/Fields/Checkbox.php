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
 * Class Checkbox
 *
 * Validates checkbox group structures by inheriting standard multiselect constraints.
 */
class Checkbox extends Multiselect
{
    // Inherits all validation rules from Multiselect seamlessly
}