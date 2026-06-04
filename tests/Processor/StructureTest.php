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
 * Class StructureTest
 *
 * Validates the core structural hierarchy (Tabs -> Sections -> Groups -> Fields)
 * and enforces correct sorting order across all configuration levels.
 */
class StructureTest extends TestCase
{
    private Processor $processor;
    private SystemConfig $schema;

    /**
     * Initialize the processor and system configuration schema environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->processor = new Processor();
        $this->schema = new SystemConfig();
    }

    /**
     * Tests that valid core layout wrappers are parsed, sorted, and populated with system defaults.
     *
     * @return void
     */
    public function testProcessValidatesAndSortsHierarchyLevelsSuccessfully(): void
    {
        $rawInput = [
            'tabs' => [
                'tab_secondary' => [
                    'label'      => 'Secondary Tab',
                    'sort_order' => 50,
                ],
                'tab_primary' => [
                    'label'      => 'Primary Tab',
                    'sort_order' => 10,
                    'class'      => '  custom-tab-class  ', // Spaced out
                    'icon'       => 'icon-root',
                ],
            ],
            'sections' => [
                'section_b' => [
                    'tab'        => 'tab_primary',
                    'label'      => 'Section B',
                    'sort_order' => 20,
                ],
                'section_a' => [
                    'tab'        => 'tab_primary',
                    'label'      => 'Section A',
                    'sort_order' => 5,
                    'groups'     => [
                        'group_nested' => [
                            'label'      => 'Nested Group',
                            'sort_order' => 100,
                        ],
                    ],
                ],
            ],
        ];

        $cleanConfig = $this->processor->process($rawInput, $this->schema);

        // Verify that tabs were extracted and sorted by sort_order ascending
        $tabKeys = array_keys($cleanConfig['tabs']);
        $this->assertSame(['tab_primary', 'tab_secondary'], $tabKeys);

        // Verify string modifiers trimming inside tabs validation
        $this->assertSame('custom-tab-class', $cleanConfig['tabs']['tab_primary']['class']);

        // Verify that sections are linked correctly and sorted inside the parent tab context
        $sectionKeys = array_keys($cleanConfig['sections']);
        $this->assertSame(['section_a', 'section_b'], $sectionKeys);

        // Verify cascading structure mapping down to groups level
        $this->assertArrayHasKey('groups', $cleanConfig['sections']['section_a']);
        $this->assertArrayHasKey('group_nested', $cleanConfig['sections']['section_a']['groups']);
        $this->assertSame('Nested Group', $cleanConfig['sections']['section_a']['groups']['group_nested']['label']);
    }

    /**
     * Parameterized test verifying structural layout configuration rule violations.
     *
     * @dataProvider structuralInvalidConfigProvider
     * @param array<string, mixed> $rawInput
     * @param string $expectedMessage
     * @return void
     */
    public function testProcessThrowsExceptionOnStructuralValidationFailures(array $rawInput, string $expectedMessage): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->processor->process($rawInput, $this->schema);
    }

    /**
     * Data provider containing matrices of deformed configuration structural setups.
     *
     * @return array<string, array{rawInput: array<string, mixed>, expectedMessage: string}>
     */
    public function structuralInvalidConfigProvider(): array
    {
        return [
            'tabs_parameter_root_is_not_an_array' => [
                'rawInput'        => ['tabs' => 'string-payload'],
                'expectedMessage' => "The 'tabs' section must be an array.",
            ],
            'tab_sort_order_contains_invalid_string_type' => [
                'rawInput'        => ['tabs' => ['system_tab' => ['sort_order' => '10']]],
                'expectedMessage' => "Field 'sort_order' for tab 'system_tab' must be a strict integer.",
            ],
            'tab_label_contains_invalid_non_string_type' => [
                'rawInput'        => ['tabs' => ['system_tab' => ['label' => 404]]],
                'expectedMessage' => "Field 'label' for tab 'system_tab' must be a strict string.",
            ],
            'sections_parameter_root_is_not_an_array' => [
                'rawInput'        => ['tabs' => ['main_tab' => []], 'sections' => 'malformed-string'],
                'expectedMessage' => "The 'sections' section must be an array.",
            ],
            'section_missing_mandatory_parent_tab_assignment' => [
                'rawInput'        => [
                    'tabs'     => ['main_tab' => []],
                    'sections' => ['payment_settings' => ['label' => 'Payments']],
                ],
                'expectedMessage' => "Section 'payment_settings' is missing its mandatory 'tab' assignment parameter.",
            ],
            'section_references_an_undefined_parent_tab' => [
                'rawInput'        => [
                    'tabs'     => ['main_tab' => []],
                    'sections' => ['payment_settings' => ['tab' => 'non_existent_tab']],
                ],
                'expectedMessage' => "Section 'payment_settings' references an undefined parent tab 'non_existent_tab'.",
            ],
            'group_sort_order_contains_invalid_type' => [
                'rawInput'        => [
                    'tabs'     => ['main_tab' => []],
                    'sections' => [
                        'payment_settings' => [
                            'tab'    => 'main_tab',
                            'groups' => [
                                'auth_group' => ['sort_order' => 'five'],
                            ],
                        ],
                    ],
                ],
                'expectedMessage' => "Field 'sort_order' for group 'auth_group' must be a strict integer.",
            ],
        ];
    }
}