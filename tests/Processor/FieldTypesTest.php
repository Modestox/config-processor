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
use Modestox\ConfigProcessor\Schema\GroupedConfig;
use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Class FieldTypesTest
 *
 * Tests field type validators.
 */
class FieldTypesTest extends TestCase
{
    private Processor $processor;
    private SystemConfig $schema;

    /** Prepares fixtures. */
    protected function setUp(): void
    {
        $this->processor = new Processor();
        $this->schema = new SystemConfig();
    }

    /** Wraps a field into a minimal schema. */
    private function wrapField(array $fieldData): array
    {
        return [
            'tabs'     => ['main_tab' => []],
            'sections' => [
                'main_section' => [
                    'tab'    => 'main_tab',
                    'groups' => [
                        'main_group' => [
                            'fields' => [
                                'target_field' => $fieldData,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** Text field applies automatic trimming. */
    public function testTextFieldAppliesAutomaticTrimming(): void
    {
        $payload = $this->wrapField([
            'type'        => 'text',
            'placeholder' => '   Spaced Placeholder   ',
            'comment'     => '   Spaced Comment   ',
        ]);

        $clean = $this->processor->process($payload, $this->schema);
        $field = $clean['sections']['main_section']['groups']['main_group']['fields']['target_field'];

        $this->assertSame('Spaced Placeholder', $field['placeholder']);
        $this->assertSame('Spaced Comment', $field['comment']);
    }

    /** Image field applies automatic trimming. */
    public function testImageFieldAppliesAutomaticTrimming(): void
    {
        $payload = $this->wrapField([
            'type'       => 'image',
            'upload_dir' => '   assets/uploads   ',
            'extensions' => '   png,jpg   ',
        ]);

        $clean = $this->processor->process($payload, $this->schema);
        $field = $clean['sections']['main_section']['groups']['main_group']['fields']['target_field'];

        $this->assertSame('assets/uploads', $field['upload_dir']);
        $this->assertSame('png,jpg', $field['extensions']);
    }

    /** Yes no field injects standard options matrix automatically. */
    public function testYesNoFieldInjectsStandardOptionsMatrixAutomatically(): void
    {
        $payload = $this->wrapField(['type' => 'yes_no']);

        $clean = $this->processor->process($payload, $this->schema);
        $field = $clean['sections']['main_section']['groups']['main_group']['fields']['target_field'];

        $this->assertSame([0 => 'No', 1 => 'Yes'], $field['options']);
        $this->assertSame(0, $field['default']);
    }

    /** Datetime field accepts valid iso formats. */
    public function testDatetimeFieldAcceptsValidIsoFormats(): void
    {
        $payload = $this->wrapField([
            'type'      => 'datetime',
            'view_mode' => 'datetime',
            'default'   => '   2026-11-25 00:00:00   ',
        ]);

        $clean = $this->processor->process($payload, $this->schema);
        $field = $clean['sections']['main_section']['groups']['main_group']['fields']['target_field'];

        $this->assertSame('datetime', $field['view_mode']);
        $this->assertSame('2026-11-25 00:00:00', $field['default']);
    }

    /** Dynamic rows normalizes table matrix structures. */
    public function testDynamicRowsNormalizesTableMatrixStructures(): void
    {
        $payload = $this->wrapField([
            'type'    => 'dynamic_rows',
            'columns' => ['weight' => 'Weight', 'price' => 'Price'],
            'default' => [
                ['weight' => '  5  ', 'price' => ' 10.50 '],
            ],
        ]);

        $clean = $this->processor->process($payload, $this->schema);
        $field = $clean['sections']['main_section']['groups']['main_group']['fields']['target_field'];

        $this->assertSame(['weight' => '5', 'price' => '10.50'], $field['default'][0]);
    }

    /** Infoblock field normalizes ui attributes successfully. */
    public function testInfoblockFieldNormalizesUiAttributesSuccessfully(): void
    {
        $payload = $this->wrapField([
            'type'   => 'infoblock',
            'text'   => '   System maintenance notification text.   ',
            'format' => 'html',
            'class'  => 'custom-alert-class',
        ]);

        $clean = $this->processor->process($payload, $this->schema);
        $field = $clean['sections']['main_section']['groups']['main_group']['fields']['target_field'];

        $this->assertSame('System maintenance notification text.', $field['text']);
        $this->assertSame('html', $field['format']);
        $this->assertSame('custom-alert-class', $field['class']);
        $this->assertNull($field['depends']);
    }

    /**
     * Process throws exception on field rule violations.
     *
     * @dataProvider invalidFieldTypesProvider
     */
    public function testProcessThrowsExceptionOnFieldRuleViolations(array $fieldData, string $expectedMessage): void
    {
        $payload = $this->wrapField($fieldData);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->processor->process($payload, $this->schema);
    }

    /** Invalid field types cases. */
    public function invalidFieldTypesProvider(): array
    {
        return [
            'unsupported_field_type_provided'                       => [
                'fieldData'       => ['type' => 'unknown_type'],
                'expectedMessage' => "Unsupported type 'unknown_type' in field 'target_field'.",
            ],
            'number_min_restriction_contains_invalid_string'        => [
                'fieldData'       => ['type' => 'number', 'min' => 'ten'],
                'expectedMessage' => "Field 'min' for number field 'target_field' must be a strict integer or float.",
            ],
            'number_max_boundary_is_less_than_min_parameter'        => [
                'fieldData'       => ['type' => 'number', 'min' => 20, 'max' => 10],
                'expectedMessage' => "Field 'max' cannot be less than 'min' for number field 'target_field'.",
            ],
            'number_default_is_less_than_min_boundary'              => [
                'fieldData'       => ['type' => 'number', 'min' => 5, 'default' => 2],
                'expectedMessage' => "Default value for number field 'target_field' cannot be less than defined 'min' restriction.",
            ],
            'datetime_contains_invalid_calendar_day_string'         => [
                'fieldData'       => ['type' => 'datetime', 'view_mode' => 'date', 'default' => '2026-02-31'],
                'expectedMessage' => "Default value '2026-02-31' for field 'target_field' does not match mandatory 'date' format standard ('Y-m-d').",
            ],
            'dynamic_rows_is_missing_mandatory_columns_declaration' => [
                'fieldData'       => ['type' => 'dynamic_rows'],
                'expectedMessage' => "Field 'columns' for dynamic rows field 'target_field' must be a defined configuration array.",
            ],
            'select_contains_empty_options_array'                   => [
                'fieldData'       => ['type' => 'select', 'options' => []],
                'expectedMessage' => "Field 'target_field' of type 'select' must contain a non-empty array of 'options'.",
            ],
            'select_default_value_does_not_exist_in_options'        => [
                'fieldData'       => ['type' => 'select', 'options' => ['a' => 'Option A'], 'default' => 'b'],
                'expectedMessage' => "The default value 'b' for field 'target_field' does not exist within the permitted options scope.",
            ],
        ];
    }

    /** Field provider property normalization. */
    public function testFieldProviderPropertyNormalization(): void
    {
        $input = [
            'groups' => [
                'general_group' => [
                    'fields' => [
                        'country_list' => [
                            'type'     => 'select',
                            'options'  => [1 => 'Temporary'],
                            'provider' => '  Modestox\Core\Provider\CountryProvider  ',
                        ],
                    ],
                ],
            ],
        ];

        $clean = $this->processor->process($input, new GroupedConfig());
        $field = $clean['groups']['general_group']['fields']['country_list'];

        $this->assertEquals('Modestox\Core\Provider\CountryProvider', $field['provider']);
    }
}