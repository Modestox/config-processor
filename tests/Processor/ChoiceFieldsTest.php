<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Tests\Processor;

use Modestox\ConfigProcessor\Exception\InvalidConfigException;
use Modestox\ConfigProcessor\Processor;
use Modestox\ConfigProcessor\Schema\GroupedConfig;
use PHPUnit\Framework\TestCase;

/**
 * Tests defaults of select, radio, multiselect and checkbox fields.
 */
class ChoiceFieldsTest extends TestCase
{
    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    private function field(array $field): array
    {
        $clean = (new Processor())->process(['groups' => ['g' => ['fields' => ['f' => $field]]]], new GroupedConfig());

        return $clean['groups']['g']['fields']['f'];
    }

    /** Duplicate multiselect defaults are rejected. */
    public function testMultiselectRejectsDuplicateDefaults(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("The default value 'DE' for field 'f' is listed more than once.");

        $this->field(['type' => 'multiselect', 'options' => ['DE' => 'Germany'], 'default' => ['DE', 'DE']]);
    }

    /** Numeric string and integer are the same option. */
    public function testMultiselectTreatsNumericStringAndIntegerAsDuplicates(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('is listed more than once');

        $this->field(['type' => 'multiselect', 'options' => [1 => 'One'], 'default' => ['1', 1]]);
    }

    /** Multiselect defaults are normalized to option keys. */
    public function testMultiselectDefaultsAreNormalizedToOptionKeys(): void
    {
        $field = $this->field(['type' => 'multiselect', 'options' => [1 => 'One', 2 => 'Two', 'x' => 'X'], 'default' => ['2', 'x', 1]]);

        $this->assertSame([2, 'x', 1], $field['default']);
    }

    /** Default keys of a map are reduced to a list. */
    public function testMultiselectDefaultIsAlwaysAList(): void
    {
        $field = $this->field(['type' => 'checkbox', 'options' => ['a' => 'A', 'b' => 'B'], 'default' => ['first' => 'b']]);

        $this->assertSame(['b'], $field['default']);
    }

    /** Select default follows the type of the option key. */
    public function testSelectDefaultFollowsOptionKeyType(): void
    {
        $field = $this->field(['type' => 'select', 'options' => [1 => 'One', 2 => 'Two'], 'default' => '2']);

        $this->assertSame(2, $field['default']);
    }

    /** Select without default uses the first option. */
    public function testSelectWithoutDefaultUsesFirstOption(): void
    {
        $field = $this->field(['type' => 'radio', 'options' => ['b' => 'B', 'a' => 'A']]);

        $this->assertSame('b', $field['default']);
    }

    /** Messages name the actual field type. */
    public function testMessagesNameTheActualFieldType(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("Field 'f' of type 'radio' must contain a non-empty array of 'options'.");

        $this->field(['type' => 'radio', 'options' => []]);
    }
}