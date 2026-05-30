<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Tests;

use PHPUnit\Framework\TestCase;
use Modestox\ConfigProcessor\Merger\ConfigMerger;
use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Class ConfigMergerTest
 *
 * Automated structural tests for verifying deep configuration merging and collision containment.
 */
class ConfigMergerTest extends TestCase
{
    /**
     * Tests that configurations from separate modules merge gracefully into one tree
     * without any data losses or parameter corruptions.
     *
     * @return void
     */
    public function testMergeCombinesDistinctModulesSuccessfully(): void
    {
        $merger = new ConfigMerger();

        $moduleA = [
            'sections' => [
                'general_settings' => [
                    'groups' => [
                        'general_group' => [
                            'fields' => [
                                'site_name' => [
                                    'type'       => 'text',
                                    'label'      => 'Site Title',
                                    'sort_order' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $moduleB = [
            'sections' => [
                'general_settings' => [
                    'groups' => [
                        'general_group' => [
                            'fields' => [
                                'meta_description' => [
                                    'type'       => 'text',
                                    'label'      => 'Meta Description',
                                    'sort_order' => 2,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expectedOutput = [
            'sections' => [
                'general_settings' => [
                    'groups' => [
                        'general_group' => [
                            'fields' => [
                                'site_name'        => [
                                    'type'       => 'text',
                                    'label'      => 'Site Title',
                                    'sort_order' => 1,
                                ],
                                'meta_description' => [
                                    'type'       => 'text',
                                    'label'      => 'Meta Description',
                                    'sort_order' => 2,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualOutput = $merger->merge([$moduleA, $moduleB]);

        $this->assertEquals($expectedOutput, $actualOutput);
    }

    /**
     * Parameterized test that intercepts various schema merging malformations,
     * strict key failures, and explicit scalar parameter collisions.
     *
     * @dataProvider invalidMergeProvider
     * @param array<int, array<string, mixed>> $configs
     * @param string $expectedMessage
     * @return void
     */
    public function testMergeThrowsExceptionOnInvalidDataOrCollisions(array $configs, string $expectedMessage): void
    {
        $merger = new ConfigMerger();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($expectedMessage);

        $merger->merge($configs);
    }

    /**
     * Data provider containing matrices of conflicting or malformed multi-module configurations.
     *
     * @return array<string, array{configs: array<int, array<string, mixed>>, expectedMessage: string}>
     */
    public function invalidMergeProvider(): array
    {
        return [
            'root_configuration_index_is_not_an_array'  => [
                'configs'         => [['tabs' => []], 'string-instead-of-array'],
                'expectedMessage' => "Configuration at index 1 must be an array.",
            ],
            'integer_identifier_key_detected'           => [
                'configs'         => [
                    [
                        0 => [
                            'tabs' => [
                                0 => ['sort_order' => 1], // Zero is an integer key, not a strict string ID
                            ],
                        ],
                    ],
                ],
                'expectedMessage' => "Configuration keys must be strict strings. Integer key detected.",
            ],
            'scalar_parameter_value_mismatch_collision' => [
                'configs'         => [
                    [
                        'tabs' => [
                            'modestox_tab' => ['sort_order' => 10],
                        ],
                    ],
                    [
                        'tabs' => [
                            'modestox_tab' => ['sort_order' => 20], // Collision: different sort_orders for the same ID
                        ],
                    ],
                ],
                'expectedMessage' => "Configuration collision detected at key 'sort_order'. Value mismatch between modules.",
            ],
            'structural_type_mismatch_collision'        => [
                'configs'         => [
                    [
                        'sections' => [
                            'general_settings' => [
                                'groups' => [], // Expected structural array container
                            ],
                        ],
                    ],
                    [
                        'sections' => [
                            'general_settings' => [
                                'groups' => 'flat-string-instead-of-array', // Triggers a structure conflict
                            ],
                        ],
                    ],
                ],
                'expectedMessage' => "Configuration collision detected at key 'groups'. Value mismatch between modules.",
            ],
        ];
    }
}