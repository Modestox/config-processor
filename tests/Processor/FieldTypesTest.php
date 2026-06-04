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

use PHPUnit\Framework\TestCase;
use Modestox\ConfigProcessor\Processor;
use Modestox\ConfigProcessor\Schema\SystemConfig;
use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Class FieldTypesTest
 *
 * Automated isolated strategies tests for verifying data sanitization,
 * types boundaries, default fallbacks, and specific configuration field parameters.
 */
class FieldTypesTest extends TestCase
{
    private Processor $processor;
    private SystemConfig $schema;

    /**
     * Set up the field type verification runtime environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->processor = new Processor();
        $this->schema = new SystemConfig();
    }

    /**
     * Helper template to structure minimal nested valid layout boilerplate payload.
     *
     * @param array<string, mixed> $fieldData
     * @return array<string, mixed>
     */
    private function wrapField(array $fieldData): array
    {
        return [
            'tabs' => ['main_tab' => []],
            'sections' => [
                'main_section' => [
                    'tab' => 'main_tab',
                    'groups' => [
                        'main_group' => [
                            'fields' => [
                                'target_field' => $fieldData
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Tests that scalar input fields cleanly apply automatic trimming modifiers.
     *
     * @return void
     */
    public function testTextAndFilesApplyAutomaticTrimmingSuccessfully(): void
    {
        $payload = $this->wrapField([
            'type'        => 'text',
            'placeholder' => '   Spaced Placeholder   ',
            'comment'     => '   Spaced Comment   ',
            'upload_dir'  => '   assets/uploads   ', // For testing file inheritance behavior
            'extensions'  => '   png,jpg   '
        ]);

        $clean = $this->processor->process($payload, $this->schema);
        $field = $clean['sections']['main_section']['groups']['main_group']['fields']['target_field'];

        $this->assertSame('Spaced Placeholder', $field['placeholder']);
        $this->assertSame('Spaced Comment', $field['comment']);
    }

    /**
     * Tests that the yes_no component automatically populates standard binary options scope.
     *
     * @return void
     */
    public function testYesNoFieldInjectsStandardOptionsMatrixAutomatically(): void
    {
        $payload = $this->wrapField(['type' => 'yes_no']);

        $clean = $this->processor->process($payload, $this->schema);
        $field = $clean['sections']['main_section']['groups']['main_group']['fields']['target_field'];

        $this->assertSame([0 => 'No', 1 => 'Yes'], $field['options']);
        $this->assertSame(0, $field['default']);
    }

    /**
     * Tests that datetime component accurately processes valid ISO calendars.
     *
     * @return void
     */
    public function testDatetimeFieldAcceptsValidIsoFormats(): void
    {
        $payload = $this->wrapField([
            'type'      => 'datetime',
            'view_mode' => 'datetime',
            'default'   => '   2026-11-25 00:00:00   '
        ]);

        $clean = $this->processor->process($payload, $this->schema);
        $field = $clean['sections']['main_section']['groups']['main_group']['fields']['target_field'];

        $this->assertSame('datetime', $field['view_mode']);
        $this->assertSame('2026-11-25 00:00:00', $field['default']);
    }

    /**
     * Tests that dynamic rows clean cells and preserve multi-dimensional matrix layout.
     *
     * @return void
     */
    public function testDynamicRowsNormalizesTableMatrixStructures(): void
    {
        $payload = $this->wrapField([
            'type'    => 'dynamic_rows',
            'columns' => ['weight' => 'Weight', 'price' => 'Price'],
            'default' => [
                ['weight' => '  5  ', 'price' => ' 10.50 ']
            ]
        ]);

        $clean = $this->processor->process($payload, $this->schema);
        $field = $clean['sections']['main_section']['groups']['main_group']['fields']['target_field'];

        $this->assertSame(['weight' => '5', 'price' => '10.50'], $field['default'][0]);
    }

    /**
     * Tests that infoblock UI elements are compiled and validated properly with text and html formats.
     *
     * @return void
     */
    public function testInfoblockFieldNormalizesUiAttributesSuccessfully(): void
    {
        $payload = $this->wrapField([
            'type'   => 'infoblock',
            'text'   => '   System maintenance notification text.   ',
            'format' => 'html',
            'class'  => 'custom-alert-class'
        ]);

        $clean = $this->processor->process($payload, $this->schema);
        $field = $clean['sections']['main_section']['groups']['main_group']['fields']['target_field'];

        $this->assertSame('System maintenance notification text.', $field['text']);
        $this->assertSame('html', $field['format']);
        $this->assertSame('custom-alert-class', $field['class']);
        $this->assertNull($field['depends']);
    }

    /**
     * Parameterized test intercepting specific data structure or field type rule violations.
     *
     * @dataProvider invalidFieldTypesProvider
     * @param array<string, mixed> $fieldData
     * @param string $expectedMessage
     * @return void
     */
    public function testProcessThrowsExceptionOnFieldRuleViolations(array $fieldData, string $expectedMessage): void
    {
        $payload = $this->wrapField($fieldData);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->processor->process($payload, $this->schema);
    }

    /**
     * Data provider distributing unique error states for distinct input strategies.
     *
     * @return array<string, array{fieldData: array<string, mixed>, expectedMessage: string}>
     */
    public function invalidFieldTypesProvider(): array
    {
        return [
            'unsupported_field_type_provided' => [
                'fieldData'       => ['type' => 'unknown_type'],
                'expectedMessage' => "Unsupported type 'unknown_type' in field 'target_field'.",
            ],
            'number_min_restriction_contains_invalid_string' => [
                'fieldData'       => ['type' => 'number', 'min' => 'ten'],
                'expectedMessage' => "Field 'min' for number field 'target_field' must be a strict integer or float.",
            ],
            'number_max_boundary_is_less_than_min_parameter' => [
                'fieldData'       => ['type' => 'number', 'min' => 20, 'max' => 10],
                'expectedMessage' => "Field 'max' cannot be less than 'min' for number field 'target_field'.",
            ],
            'number_default_is_less_than_min_boundary' => [
                'fieldData'       => ['type' => 'number', 'min' => 5, 'default' => 2],
                'expectedMessage' => "Default value for number field 'target_field' cannot be less than defined 'min' restriction.",
            ],
            'datetime_contains_invalid_calendar_day_string' => [
                'fieldData'       => ['type' => 'datetime', 'view_mode' => 'date', 'default' => '2026-02-31'],
                'expectedMessage' => "Default value '2026-02-31' for field 'target_field' does not match mandatory 'date' format standard ('Y-m-d').",
            ],
            'dynamic_rows_is_missing_mandatory_columns_declaration' => [
                'fieldData'       => ['type' => 'dynamic_rows'],
                'expectedMessage' => "Field 'columns' for dynamic rows field 'target_field' must be a defined configuration array.",
            ],
            'select_contains_empty_options_array' => [
                'fieldData'       => ['type' => 'select', 'options' => []],
                'expectedMessage' => "Field 'target_field' of type 'select' must contain a non-empty array of 'options'.",
            ],
            'select_default_value_does_not_exist_in_options' => [
                'fieldData'       => ['type' => 'select', 'options' => ['a' => 'Option A'], 'default' => 'b'],
                'expectedMessage' => "The default value 'b' for field 'target_field' does not exist within the permitted options scope.",
            ],
        ];
    }
}