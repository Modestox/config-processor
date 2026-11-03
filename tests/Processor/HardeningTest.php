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

use InvalidArgumentException;
use Modestox\ConfigProcessor\Exception\InvalidConfigException;
use Modestox\ConfigProcessor\Processor;
use Modestox\ConfigProcessor\Schema\GroupedConfig;
use Modestox\ConfigProcessor\Schema\SystemConfig;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\Text;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for edge cases and hardening rules.
 */
class HardeningTest extends TestCase
{
    private Processor $processor;

    protected function setUp(): void
    {
        $this->processor = new Processor();
    }

    /**
     * Processes fields inside a single group and returns the clean fields.
     *
     * @param array<string, mixed> $fields
     * @return array<string, array<string, mixed>>
     */
    private function fields(array $fields): array
    {
        $clean = $this->processor->process(
            ['groups' => ['main_group' => ['fields' => $fields]]],
            new GroupedConfig(),
        );

        return $clean['groups']['main_group']['fields'];
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function assertFieldsRejected(array $fields, string $expectedMessage): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->fields($fields);
    }

    /** Identifier with a trailing newline is rejected. */
    public function testIdentifierWithTrailingNewlineIsRejected(): void
    {
        $this->assertFieldsRejected(["name\n" => ['type' => 'text']], 'contains invalid characters');
    }

    /** Tab identifier with a trailing newline is rejected. */
    public function testTabIdentifierWithTrailingNewlineIsRejected(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("The tab identifier key 'main\n' contains invalid characters.");

        $this->processor->process(['tabs' => ["main\n" => []]], new SystemConfig());
    }

    /** Numeric identifier is rejected with an explicit message. */
    public function testNumericIdentifierIsRejectedWithExplanation(): void
    {
        $this->assertFieldsRejected(['123' => ['type' => 'text']], 'Numeric keys are cast to integers by PHP');
    }

    /** Tabs and sections always expose class and icon. */
    public function testTabsAndSectionsBackfillClassAndIcon(): void
    {
        $clean = $this->processor->process([
            'tabs'     => ['main_tab' => []],
            'sections' => ['main_section' => ['tab' => 'main_tab']],
        ], new SystemConfig());

        $this->assertSame(['label' => 'main_tab', 'sort_order' => 0, 'class' => '', 'icon' => ''], $clean['tabs']['main_tab']);
        $this->assertSame('', $clean['sections']['main_section']['class']);
        $this->assertSame('', $clean['sections']['main_section']['icon']);
    }

    /**
     * Number default stays inside the declared range.
     *
     * @dataProvider numberDefaultProvider
     * @param array<string, int|float> $bounds
     */
    public function testNumberDefaultFallsBackInsideDeclaredRange(array $bounds, int|float $expected): void
    {
        $clean = $this->fields(['amount' => ['type' => 'number'] + $bounds]);

        $this->assertSame($expected, $clean['amount']['default']);
    }

    /**
     * @return array<string, array{bounds: array<string, int|float>, expected: int|float}>
     */
    public static function numberDefaultProvider(): array
    {
        return [
            'no_bounds'          => ['bounds' => [], 'expected' => 0],
            'min_above_zero'     => ['bounds' => ['min' => 5], 'expected' => 5],
            'max_below_zero'     => ['bounds' => ['max' => -3], 'expected' => -3],
            'zero_inside_range'  => ['bounds' => ['min' => -5, 'max' => 5], 'expected' => 0],
            'float_min_above_0'  => ['bounds' => ['min' => 0.5], 'expected' => 0.5],
        ];
    }

    /**
     * Non-finite numbers are rejected.
     *
     * @dataProvider nonFiniteNumberProvider
     */
    public function testNumberRejectsNonFiniteValues(string $key, float $value): void
    {
        $this->assertFieldsRejected(['amount' => ['type' => 'number', $key => $value]], 'must be a finite number');
    }

    /**
     * @return array<string, array{key: string, value: float}>
     */
    public static function nonFiniteNumberProvider(): array
    {
        return [
            'nan_min'      => ['key' => 'min', 'value' => NAN],
            'inf_max'      => ['key' => 'max', 'value' => INF],
            'neg_inf_def'  => ['key' => 'default', 'value' => -INF],
        ];
    }

    /**
     * Invalid dynamic rows definitions are rejected.
     *
     * @dataProvider invalidDynamicRowsProvider
     * @param array<string, mixed> $fieldData
     */
    public function testDynamicRowsRejectsInvalidDefinitions(array $fieldData, string $expectedMessage): void
    {
        $this->assertFieldsRejected(['table' => ['type' => 'dynamic_rows'] + $fieldData], $expectedMessage);
    }

    /**
     * @return array<string, array{fieldData: array<string, mixed>, expectedMessage: string}>
     */
    public static function invalidDynamicRowsProvider(): array
    {
        return [
            'empty_columns'          => [
                'fieldData'       => ['columns' => []],
                'expectedMessage' => 'must declare at least one column',
            ],
            'blank_column_id'        => [
                'fieldData'       => ['columns' => ['   ' => 'Label']],
                'expectedMessage' => 'Column identifier for dynamic rows field',
            ],
            'duplicate_column_after_trim' => [
                'fieldData'       => ['columns' => ['a' => 'A', ' a' => 'B']],
                'expectedMessage' => "Column 'a' is declared more than once",
            ],
            'unknown_column_in_row'  => [
                'fieldData'       => ['columns' => ['a' => 'A'], 'default' => [['a' => '1', 'zzz' => '2']]],
                'expectedMessage' => "contains unknown column 'zzz'",
            ],
            'array_cell_value'       => [
                'fieldData'       => ['columns' => ['a' => 'A'], 'default' => [['a' => ['x']]]],
                'expectedMessage' => 'must be a string or a number',
            ],
        ];
    }

    /** Numeric cells are cast to trimmed strings. */
    public function testDynamicRowsCastsNumericCellsToStrings(): void
    {
        $clean = $this->fields(['table' => [
            'type'    => 'dynamic_rows',
            'columns' => ['qty' => 'Qty', 'price' => 'Price'],
            'default' => [['qty' => 5, 'price' => 9.5]],
        ]]);

        $this->assertSame(['qty' => '5', 'price' => '9.5'], $clean['table']['default'][0]);
    }

    /** Self-dependency is rejected. */
    public function testFieldCannotDependOnItself(): void
    {
        $this->assertFieldsRejected(
            ['a' => ['type' => 'text', 'depends' => ['a' => '1']]],
            'Circular dependency detected between fields: a -> a.',
        );
    }

    /** Dependency cycle is rejected. */
    public function testCircularDependencyIsRejected(): void
    {
        $this->assertFieldsRejected([
            'a' => ['type' => 'text', 'depends' => ['b' => '1']],
            'b' => ['type' => 'text', 'depends' => ['c' => '1']],
            'c' => ['type' => 'text', 'depends' => ['a' => '1']],
        ], 'Circular dependency detected between fields: a -> b -> c -> a.');
    }

    /** Dependency chain without a cycle is accepted. */
    public function testDependencyChainWithoutCycleIsAccepted(): void
    {
        $clean = $this->fields([
            'a' => ['type' => 'text', 'depends' => ['b' => '1']],
            'b' => ['type' => 'text', 'depends' => ['c' => '1']],
            'c' => ['type' => 'text'],
            'd' => ['type' => 'text', 'depends' => ['b' => '1', 'c' => '1']],
        ]);

        $this->assertSame(['b' => '1'], $clean['a']['depends']);
        $this->assertNull($clean['c']['depends']);
    }

    /** Unsupported datetime view mode is rejected. */
    public function testDatetimeRejectsUnsupportedViewMode(): void
    {
        $this->assertFieldsRejected(
            ['when' => ['type' => 'datetime', 'view_mode' => 'dat']],
            "Unsupported view_mode 'dat'",
        );
    }

    /** Non-string datetime view mode is rejected. */
    public function testDatetimeRejectsNonStringViewMode(): void
    {
        $this->assertFieldsRejected(
            ['when' => ['type' => 'datetime', 'view_mode' => 5]],
            "Field 'view_mode' for temporal field 'when' must be a strict string.",
        );
    }

    /**
     * Unsafe upload directories are rejected.
     *
     * @dataProvider unsafeUploadDirProvider
     */
    public function testFileRejectsUnsafeUploadDir(string $uploadDir): void
    {
        $this->assertFieldsRejected(['doc' => ['type' => 'file', 'upload_dir' => $uploadDir]], "must not contain '..' segments");
    }

    /**
     * @return array<string, array{uploadDir: string}>
     */
    public static function unsafeUploadDirProvider(): array
    {
        return [
            'parent_segment'       => ['uploadDir' => '../secret'],
            'nested_parent'        => ['uploadDir' => 'media/../../secret'],
            'windows_parent'       => ['uploadDir' => 'media\\..\\secret'],
            'null_byte'            => ['uploadDir' => "media\0/x"],
        ];
    }

    /** Dots inside names are not treated as traversal. */
    public function testFileAcceptsDotsInsideSegmentNames(): void
    {
        $clean = $this->fields(['doc' => ['type' => 'file', 'upload_dir' => 'media/v1..2/files']]);

        $this->assertSame('media/v1..2/files', $clean['doc']['upload_dir']);
    }

    /** File extensions are stripped of whitespace. */
    public function testFileExtensionsAreStrippedOfWhitespace(): void
    {
        $clean = $this->fields(['pic' => ['type' => 'image', 'extensions' => ' jpg, png ,webp ']]);

        $this->assertSame('jpg,png,webp', $clean['pic']['extensions']);
    }

    /**
     * Malformed extension lists are rejected.
     *
     * @dataProvider invalidExtensionsProvider
     */
    public function testFileRejectsMalformedExtensions(string $extensions): void
    {
        $this->assertFieldsRejected(['doc' => ['type' => 'file', 'extensions' => $extensions]], 'comma-separated list of extensions');
    }

    /**
     * @return array<string, array{extensions: string}>
     */
    public static function invalidExtensionsProvider(): array
    {
        return [
            'leading_dot'    => ['extensions' => '.jpg'],
            'double_comma'   => ['extensions' => 'jpg,,png'],
            'trailing_comma' => ['extensions' => 'jpg,'],
            'wildcard'       => ['extensions' => '*'],
        ];
    }

    /** Invalid custom validator map is rejected. */
    public function testFieldsConstructorRejectsInvalidValidators(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Fields(['color' => new \stdClass()]);
    }

    /** Invalid custom type name is rejected. */
    public function testRegisterTypeRejectsInvalidTypeName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Fields())->registerType('Color Picker', new Text());
    }

    /** Custom type can be registered and used. */
    public function testCustomTypeCanBeRegistered(): void
    {
        $fields = new Fields();
        $fields->registerType('colorpicker', new Text());

        $clean = $fields->validate(['accent' => ['type' => 'colorpicker', 'default' => ' #fff ']]);

        $this->assertSame('colorpicker', $clean['accent']['type']);
        $this->assertSame('#fff', $clean['accent']['default']);
    }
}