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
 * Tests that depends values make sense for the parent field.
 */
class DependencyValueTest extends TestCase
{
    /**
     * @param array<string, mixed> $parent
     * @return array<string, array<string, mixed>>
     */
    private function process(array $parent, mixed $expected): array
    {
        $clean = (new Processor())->process(['groups' => ['g' => ['fields' => [
            'parent' => $parent,
            'child'  => ['type' => 'text', 'depends' => ['parent' => $expected]],
        ]]]], new GroupedConfig());

        return $clean['groups']['g']['fields'];
    }

    /**
     * @param array<string, mixed> $parent
     */
    private function assertRejected(array $parent, mixed $expected, string $message): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $this->process($parent, $expected);
    }

    /** Infoblock cannot be a parent. */
    public function testInfoblockCannotBeParent(): void
    {
        $this->assertRejected(['type' => 'infoblock', 'text' => 'Note'], '1', "cannot depend on infoblock 'parent'");
    }

    /**
     * Value must be one of the options of a choice parent.
     *
     * @dataProvider unknownOptionValueProvider
     * @param array<string, mixed> $parent
     */
    public function testExpectedValueMustBeAmongOptions(array $parent, mixed $expected): void
    {
        $this->assertRejected($parent, $expected, "which is not one of its options");
    }

    /**
     * @return array<string, array{parent: array<string, mixed>, expected: mixed}>
     */
    public static function unknownOptionValueProvider(): array
    {
        $select = ['type' => 'select', 'options' => ['a' => 'A', 'b' => 'B']];

        return [
            'select_unknown_value' => ['parent' => $select, 'expected' => 'zzz'],
            'select_bool_value'    => ['parent' => $select, 'expected' => true],
            'radio_unknown_value'  => ['parent' => ['type' => 'radio', 'options' => ['a' => 'A']], 'expected' => 'b'],
            'yes_no_unknown_value' => ['parent' => ['type' => 'yes_no'], 'expected' => 5],
            'yes_no_bool_value'    => ['parent' => ['type' => 'yes_no'], 'expected' => true],
        ];
    }

    /** Known option values are accepted, numeric strings match integer keys. */
    public function testKnownOptionValuesAreAccepted(): void
    {
        $fields = $this->process(['type' => 'select', 'options' => ['a' => 'A', 1 => 'One']], 'a');
        $this->assertSame(['parent' => 'a'], $fields['child']['depends']);

        $fields = $this->process(['type' => 'select', 'options' => ['a' => 'A', 1 => 'One']], '1');
        $this->assertSame(['parent' => '1'], $fields['child']['depends']);

        $fields = $this->process(['type' => 'yes_no'], 1);
        $this->assertSame(['parent' => 1], $fields['child']['depends']);
    }

    /** Boolean parent expects a boolean. */
    public function testBooleanParentExpectsBoolean(): void
    {
        $this->assertRejected(['type' => 'boolean'], 1, "from boolean field 'parent'; use true or false");
    }

    /** Boolean parent accepts true and false. */
    public function testBooleanParentAcceptsBooleans(): void
    {
        $this->assertSame(['parent' => false], $this->process(['type' => 'boolean'], false)['child']['depends']);
    }

    /** Number parent expects a number. */
    public function testNumberParentExpectsNumber(): void
    {
        $this->assertRejected(['type' => 'number'], '5', "from number field 'parent'; use an integer or a float");
    }

    /** Number parent accepts integers and floats. */
    public function testNumberParentAcceptsNumbers(): void
    {
        $this->assertSame(['parent' => 5], $this->process(['type' => 'number'], 5)['child']['depends']);
        $this->assertSame(['parent' => 0.5], $this->process(['type' => 'number'], 0.5)['child']['depends']);
    }

    /** Parents without a defined value shape are not checked. */
    public function testOtherParentTypesAreNotChecked(): void
    {
        $this->assertSame(['parent' => 'anything'], $this->process(['type' => 'text'], 'anything')['child']['depends']);
        $this->assertSame(['parent' => 'x'], $this->process(['type' => 'multiselect', 'options' => ['x' => 'X']], 'x')['child']['depends']);
        $this->assertSame(['parent' => 'zzz'], $this->process(['type' => 'multiselect', 'options' => ['x' => 'X']], 'zzz')['child']['depends']);
    }
}