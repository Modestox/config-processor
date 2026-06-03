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
 * Class DependencyTest
 *
 * Validates cross-field dependency constraints ('depends' configurations)
 * supporting both single and multiple parent conditions inside a group.
 */
class DependencyTest extends TestCase
{
    private Processor $processor;
    private SystemConfig $schema;

    /**
     * Set up the validation orchestrator environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->processor = new Processor();
        $this->schema = new SystemConfig();
    }

    /**
     * Tests that a standard single dependency declaration correctly passes validation.
     *
     * @return void
     */
    public function testProcessValidatesSuccessfulSingleDependsCondition(): void
    {
        $rawInput = [
            'tabs' => ['main_tab' => []],
            'sections' => [
                'api_settings' => [
                    'tab' => 'main_tab',
                    'groups' => [
                        'auth_group' => [
                            'fields' => [
                                'enable_api' => [
                                    'type' => 'yes_no',
                                    'sort_order' => 1,
                                    'default' => 1,
                                ],
                                'api_token' => [
                                    'type' => 'text',
                                    'sort_order' => 2,
                                    'depends' => [
                                        'enable_api' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $cleanConfig = $this->processor->process($rawInput, $this->schema);

        $fields = $cleanConfig['sections']['api_settings']['groups']['auth_group']['fields'];

        $this->assertArrayHasKey('depends', $fields['api_token']);
        $this->assertSame(['enable_api' => 1], $fields['api_token']['depends']);
        $this->assertNull($fields['enable_api']['depends']);
    }

    /**
     * Tests that multiple dependency conditions (Enterprise logic) are successfully compiled.
     *
     * @return void
     */
    public function testProcessValidatesSuccessfulMultipleDependsConditions(): void
    {
        $rawInput = [
            'tabs' => ['main_tab' => []],
            'sections' => [
                'api_settings' => [
                    'tab' => 'main_tab',
                    'groups' => [
                        'auth_group' => [
                            'fields' => [
                                'enable_api' => [
                                    'type' => 'yes_no',
                                    'sort_order' => 1,
                                ],
                                'api_mode' => [
                                    'type' => 'select',
                                    'sort_order' => 2,
                                    'options' => ['live' => 'Live', 'sandbox' => 'Sandbox'],
                                ],
                                'live_secret_key' => [
                                    'type' => 'password',
                                    'sort_order' => 3,
                                    'depends' => [
                                        'enable_api' => 1,
                                        'api_mode'   => 'live', // Depends on both parent fields simultaneously
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $cleanConfig = $this->processor->process($rawInput, $this->schema);

        $fields = $cleanConfig['sections']['api_settings']['groups']['auth_group']['fields'];

        $expectedDepends = [
            'enable_api' => 1,
            'api_mode'   => 'live'
        ];

        $this->assertSame($expectedDepends, $fields['live_secret_key']['depends']);
    }

    /**
     * Parameterized test catching dynamic field visibility dependency violations.
     *
     * @dataProvider invalidDependencyProvider
     * @param array<string, mixed> $rawInput
     * @param string $expectedMessage
     * @return void
     */
    public function testProcessThrowsExceptionOnDependencyMalformations(array $rawInput, string $expectedMessage): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->processor->process($rawInput, $this->schema);
    }

    /**
     * Data provider containing matrices of deformed dependency parameters.
     *
     * @return array<string, array{dirtyInput: array<string, mixed>, expectedMessage: string}>
     */
    public function invalidDependencyProvider(): array
    {
        $baseSkeleton = [
            'tabs' => ['main_tab' => []],
            'sections' => [
                'api_settings' => [
                    'tab' => 'main_tab',
                    'groups' => [
                        'auth_group' => [
                            'fields' => [],
                        ],
                    ],
                ],
            ],
        ];

        return [
            'depends_parameter_is_not_an_array' => [
                'dirtyInput' => array_replace_recursive($baseSkeleton, [
                    'sections' => ['api_settings' => ['groups' => ['auth_group' => ['fields' => [
                        'api_token' => ['type' => 'text', 'depends' => 'enable_api:1']
                    ]]]]]
                ]),
                'expectedMessage' => "The 'depends' parameter for field 'api_token' must be an array.",
            ],
            'depends_array_is_provided_but_empty' => [
                'dirtyInput' => array_replace_recursive($baseSkeleton, [
                    'sections' => ['api_settings' => ['groups' => ['auth_group' => ['fields' => [
                        'enable_api' => ['type' => 'yes_no'],
                        'api_token'  => ['type' => 'text', 'depends' => []]
                    ]]]]]
                ]),
                'expectedMessage' => "The 'depends' array for field 'api_token' cannot be empty.",
            ],
            'depends_references_undefined_field_within_the_group' => [
                'dirtyInput' => array_replace_recursive($baseSkeleton, [
                    'sections' => ['api_settings' => ['groups' => ['auth_group' => ['fields' => [
                        'api_token' => ['type' => 'text', 'depends' => ['missing_field_id' => 1]]
                    ]]]]]
                ]),
                'expectedMessage' => "Field 'api_token' depends on an undefined parent field 'missing_field_id' within the same group.",
            ],
            'depends_target_value_is_not_a_valid_scalar' => [
                'dirtyInput' => array_replace_recursive($baseSkeleton, [
                    'sections' => ['api_settings' => ['groups' => ['auth_group' => ['fields' => [
                        'enable_api' => ['type' => 'yes_no'],
                        'api_token'  => ['type' => 'text', 'depends' => ['enable_api' => [1, 2]]]
                    ]]]]]
                ]),
                'expectedMessage' => "The dependency target value for field 'api_token' must be a strict scalar value.",
            ],
        ];
    }
}