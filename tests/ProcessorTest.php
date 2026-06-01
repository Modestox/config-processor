<?php
/**
 * Modestox CMS - E-commerce Platform
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   AGPL-3.0-or-later
 * @link      https://github.com/Modestox/modestox
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Tests;

use PHPUnit\Framework\TestCase;
use Modestox\ConfigProcessor\Processor;
use Modestox\ConfigProcessor\Schema\SystemConfig;
use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Class ProcessorTest
 *
 * Automated integration tests for the multi-layered Magento-like Modestox Config Processor.
 */
class ProcessorTest extends TestCase
{
    /**
     * Tests that a valid multi-dimensional configuration structure (tabs -> sections -> groups -> fields)
     * is completely sanitized, verified, filled with appropriate fallbacks, and sorted on all levels.
     *
     * @return void
     */
    public function testProcessValidatesAndSanitizesFullConfigurationSuccessfully(): void
    {
        $processor = new Processor();
        $schema = new SystemConfig();

        $dirtyInput = [
            'tabs'     => [
                'modestox_modules' => [
                    'label'      => 'Modestox CMS Modules',
                    'sort_order' => 20,
                    'class'      => 'cms-tab',
                    'icon'       => 'fa fa-cubes',
                ],
                'payment_tab'      => [
                    'label'      => 'Sales & Payments',
                    'sort_order' => 10,
                    'class'      => 'sales-tab',
                    'icon'       => 'fa fa-credit-card',
                ],
            ],
            'sections' => [
                'general_settings' => [
                    'tab'        => 'modestox_modules',
                    'label'      => 'General Settings',
                    'sort_order' => 15,
                    'class'      => 'general-sec',
                    'icon'       => 'fa fa-gear',
                    'groups'     => [
                        'basic_group'    => [
                            'label'      => 'Basic System Core Configurations',
                            'sort_order' => 30,
                            'fields'     => [
                                'google_analytics_id' => [
                                    'type'        => 'text',
                                    'label'       => 'Google Analytics ID',
                                    'sort_order'  => 50,
                                    'placeholder' => '   UA-XXXXX-Y   ',
                                    'comment'     => 'Enter your production tracking code.',
                                    'class'       => 'input-analytics',
                                    'validation'  => ['required', '  min:10  '],
                                    'required'    => true,
                                ],
                                'site_name'           => [
                                    'type'       => 'text',
                                    'label'      => 'Site Title',
                                    'sort_order' => 10,
                                    'default'    => 'Modestox Store',
                                ],
                                'enable_cache'        => [
                                    'type'       => 'boolean',
                                    'label'      => 'Enable System Cache',
                                    'sort_order' => 5,
                                    'comment'    => '  Recommended for production speed.  ',
                                    'class'      => 'toggle-cache',
                                ],
                            ],
                        ],
                        'advanced_group' => [
                            'label'      => 'Advanced Infrastructure Cryptography',
                            'sort_order' => 10,
                            'fields'     => [
                                'session_lifetime'         => [
                                    'type'       => 'number',
                                    'label'      => 'Admin Session Lifetime',
                                    'sort_order' => 45,
                                    'default'    => 3600,
                                    'min'        => 60,
                                    'max'        => 86400,
                                ],
                                'use_secure_urls'          => [
                                    'type'       => 'yes_no',
                                    'label'      => 'Use Secure URLs',
                                    'sort_order' => 5,
                                    'default'    => 1,
                                ],
                                'promo_start_date'         => [
                                    'type'       => 'datetime',
                                    'view_mode'  => 'datetime',
                                    'label'      => 'Promotion Start',
                                    'sort_order' => 70,
                                    'default'    => '2026-11-25 00:00:00',
                                ],
                                'store_launch_date'        => [
                                    'type'       => 'datetime',
                                    'view_mode'  => 'date',
                                    'label'      => 'Launch Date',
                                    'sort_order' => 80,
                                    'default'    => '2026-06-01',
                                ],
                                'payment_gateway_password' => [
                                    'type'       => 'password',
                                    'label'      => 'Gateway Secret Password',
                                    'sort_order' => 110,
                                    'default'    => '   super-secret-key   ',
                                ],
                                'shipping_cost_matrix'     => [
                                    'type'       => 'dynamic_rows',
                                    'label'      => 'Weight Shipping Rates',
                                    'sort_order' => 120,
                                    'columns'    => [
                                        'max_weight' => 'Max Weight (kg)',
                                        'price'      => 'Rate Price (EUR)',
                                    ],
                                    'default'    => [
                                        ['max_weight' => ' 5 ', 'price' => ' 4.90 '],
                                        ['max_weight' => ' 10 ', 'price' => ' 8.50 '],
                                    ],
                                ],
                                'enable_api_integration' => [
                                    'type'       => 'yes_no',
                                    'label'      => 'Enable Remote API',
                                    'sort_order' => 200,
                                    'default'    => 0,
                                ],
                                'api_secret_token' => [
                                    'type'       => 'text',
                                    'label'       => 'API Secret Token',
                                    'sort_order' => 210,
                                    'depends'    => [
                                        'enable_api_integration' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expectedOutput = [
            'tabs'     => [
                'payment_tab'      => [
                    'label'      => 'Sales & Payments',
                    'sort_order' => 10,
                    'class'      => 'sales-tab',
                    'icon'       => 'fa fa-credit-card',
                ],
                'modestox_modules' => [
                    'label'      => 'Modestox CMS Modules',
                    'sort_order' => 20,
                    'class'      => 'cms-tab',
                    'icon'       => 'fa fa-cubes',
                ],
            ],
            'sections' => [
                'general_settings' => [
                    'tab'        => 'modestox_modules',
                    'label'      => 'General Settings',
                    'sort_order' => 15,
                    'class'      => 'general-sec',
                    'icon'       => 'fa fa-gear',
                    'groups'     => [
                        'advanced_group' => [
                            'label'      => 'Advanced Infrastructure Cryptography',
                            'sort_order' => 10,
                            'fields'     => [
                                'use_secure_urls'          => [
                                    'type'       => 'yes_no',
                                    'label'      => 'Use Secure URLs',
                                    'sort_order' => 5,
                                    'options'    => [
                                        0 => 'No',
                                        1 => 'Yes',
                                    ],
                                    'default'    => 1,
                                    'comment'    => '',
                                    'class'      => '',
                                    'validation' => [],
                                    'required'   => false,
                                    'depends'    => null,
                                ],
                                'session_lifetime'         => [
                                    'type'       => 'number',
                                    'label'      => 'Admin Session Lifetime',
                                    'sort_order' => 45,
                                    'default'    => 3600,
                                    'min'        => 60,
                                    'max'        => 86400,
                                    'comment'    => '',
                                    'class'      => '',
                                    'validation' => [],
                                    'required'   => false,
                                    'depends'    => null,
                                ],
                                'promo_start_date'         => [
                                    'type'       => 'datetime',
                                    'label'      => 'Promotion Start',
                                    'sort_order' => 70,
                                    'view_mode'  => 'datetime',
                                    'default'    => '2026-11-25 00:00:00',
                                    'comment'    => '',
                                    'class'      => '',
                                    'validation' => [],
                                    'required'   => false,
                                    'depends'    => null,
                                ],
                                'store_launch_date'        => [
                                    'type'       => 'datetime',
                                    'label'      => 'Launch Date',
                                    'sort_order' => 80,
                                    'view_mode'  => 'date',
                                    'default'    => '2026-06-01',
                                    'comment'    => '',
                                    'class'      => '',
                                    'validation' => [],
                                    'required'   => false,
                                    'depends'    => null,
                                ],
                                'payment_gateway_password' => [
                                    'type'       => 'password',
                                    'label'      => 'Gateway Secret Password',
                                    'sort_order' => 110,
                                    'default'    => 'super-secret-key',
                                    'comment'    => '',
                                    'class'      => '',
                                    'validation' => [],
                                    'required'   => false,
                                    'depends'    => null,
                                ],
                                'shipping_cost_matrix'     => [
                                    'type'       => 'dynamic_rows',
                                    'label'      => 'Weight Shipping Rates',
                                    'sort_order' => 120,
                                    'columns'    => [
                                        'max_weight' => 'Max Weight (kg)',
                                        'price'      => 'Rate Price (EUR)',
                                    ],
                                    'default'    => [
                                        ['max_weight' => '5', 'price' => '4.90'],
                                        ['max_weight' => '10', 'price' => '8.50'],
                                    ],
                                    'comment'    => '',
                                    'class'      => '',
                                    'validation' => [],
                                    'required'   => false,
                                    'depends'    => null,
                                ],
                                'enable_api_integration' => [
                                    'type'       => 'yes_no',
                                    'label'      => 'Enable Remote API',
                                    'sort_order' => 200,
                                    'options'    => [
                                        0 => 'No',
                                        1 => 'Yes',
                                    ],
                                    'default'    => 0, // ДОБАВЛЕНО СТРОГО СЮДА С СОБЛЮДЕНИЕМ ПОРЯДКА КЛЮЧЕЙ РОДИТЕЛЬСКОГО SELECT
                                    'comment'    => '',
                                    'class'      => '',
                                    'validation' => [],
                                    'required'   => false,
                                    'depends'    => null,
                                ],
                                'api_secret_token' => [
                                    'type'        => 'text',
                                    'label'       => 'API Secret Token',
                                    'sort_order'  => 210,
                                    'default'     => '',
                                    'placeholder' => '',
                                    'comment'     => '',
                                    'class'       => '',
                                    'validation'  => [],
                                    'required'    => false,
                                    'depends'     => [
                                        'enable_api_integration' => 1,
                                    ],
                                ],
                            ],
                        ],
                        'basic_group'    => [
                            'label'      => 'Basic System Core Configurations',
                            'sort_order' => 30,
                            'fields'     => [
                                'enable_cache'        => [
                                    'type'       => 'boolean',
                                    'label'      => 'Enable System Cache',
                                    'sort_order' => 5,
                                    'default'    => false,
                                    'comment'    => 'Recommended for production speed.',
                                    'class'      => 'toggle-cache',
                                    'validation' => [],
                                    'required'   => false,
                                    'depends'    => null,
                                ],
                                'site_name'           => [
                                    'type'        => 'text',
                                    'label'       => 'Site Title',
                                    'sort_order'  => 10,
                                    'default'     => 'Modestox Store',
                                    'placeholder' => '',
                                    'comment'     => '',
                                    'class'       => '',
                                    'validation'  => [],
                                    'required'    => false,
                                    'depends'     => null,
                                ],
                                'google_analytics_id' => [
                                    'type'        => 'text',
                                    'label'       => 'Google Analytics ID',
                                    'sort_order'  => 50,
                                    'placeholder' => 'UA-XXXXX-Y',
                                    'default'     => '',
                                    'comment'     => 'Enter your production tracking code.',
                                    'class'       => 'input-analytics',
                                    'validation'  => ['required', 'min:10'],
                                    'required'    => true,
                                    'depends'     => null,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualOutput = $processor->process($dirtyInput, $schema);

        $this->assertEquals($expectedOutput, $actualOutput);
    }

    /**
     * Parameterized test that intercepts various structure malformations
     * and asserts that the proper domain exceptions are thrown with clear messages.
     *
     * @dataProvider invalidConfigProvider
     * @param array<string, mixed> $dirtyInput
     * @param string $expectedMessage
     * @return void
     */
    public function testProcessThrowsExceptionOnInvalidData(array $dirtyInput, string $expectedMessage): void
    {
        $processor = new Processor();
        $schema = new SystemConfig();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($expectedMessage);

        $processor->process($dirtyInput, $schema);
    }

    /**
     * Data provider containing matrices of deformed configuration options.
     *
     * @return array<string, array{dirtyInput: array<string, mixed>, expectedMessage: string}>
     */
    public function invalidConfigProvider(): array
    {
        $baseStructure = [
            'tabs'     => ['main_tab' => []],
            'sections' => [
                'payment_gateways' => [
                    'tab'    => 'main_tab',
                    'groups' => [
                        'credentials_group' => [
                            'fields' => [],
                        ],
                    ],
                ],
            ],
        ];

        return [
            // Tabs validation scenarios
            'tabs_is_not_an_array'                      => [
                'dirtyInput'      => ['tabs' => 'string'],
                'expectedMessage' => "The 'tabs' section must be an array.",
            ],
            'sort_order_is_not_numeric'                 => [
                'dirtyInput'      => ['tabs' => ['modestox_modules' => ['sort_order' => '10']]],
                'expectedMessage' => "Field 'sort_order' for tab 'modestox_modules' must be a strict integer.",
            ],
            // Fields orchestration scenarios
            'field_uses_an_unsupported_type_definition' => [
                'dirtyInput'      => array_replace_recursive($baseStructure, [
                    'sections' => [
                        'payment_gateways' => [
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => [
                                        'api_key' => ['type' => 'wysiwyg'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
                'expectedMessage' => "Unsupported type 'wysiwyg' in field 'api_key'. Supported: text, boolean, select, multiselect, radio, checkbox, textarea, number, yes_no, datetime, password, dynamic_rows.",
            ],
            // Abstract Field Validation scenarios
            'field_comment_must_be_strict_string'       => [
                'dirtyInput'      => array_replace_recursive($baseStructure, [
                    'sections' => [
                        'payment_gateways' => [
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => [
                                        'api_key' => ['type' => 'text', 'comment' => 123],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
                'expectedMessage' => "Field 'comment' for field 'api_key' must be a strict string.",
            ],
            'field_required_must_be_strict_bool'        => [
                'dirtyInput'      => array_replace_recursive($baseStructure, [
                    'sections' => [
                        'payment_gateways' => [
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => [
                                        'api_key' => ['type' => 'text', 'required' => 'true'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
                'expectedMessage' => "Field 'required' for field 'api_key' must be a strict boolean.",
            ],
            // Number field validation scenarios
            'field_number_min_must_be_numeric'          => [
                'dirtyInput'      => array_replace_recursive($baseStructure, [
                    'sections' => [
                        'payment_gateways' => [
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => [
                                        'lifetime' => ['type' => 'number', 'min' => '60'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
                'expectedMessage' => "Field 'min' for number field 'lifetime' must be a strict integer or float.",
            ],
            'field_number_max_less_than_min'            => [
                'dirtyInput'      => array_replace_recursive($baseStructure, [
                    'sections' => [
                        'payment_gateways' => [
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => [
                                        'lifetime' => ['type' => 'number', 'min' => 100, 'max' => 50],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
                'expectedMessage' => "Field 'max' cannot be less than 'min' for number field 'lifetime'.",
            ],
            // Datetime validation scenarios
            'field_datetime_invalid_format'             => [
                'dirtyInput'      => array_replace_recursive($baseStructure, [
                    'sections' => [
                        'payment_gateways' => [
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => [
                                        'start_date' => ['type' => 'datetime', 'view_mode' => 'date', 'default' => '2026/12/31'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
                'expectedMessage' => "Default value '2026/12/31' for field 'start_date' does not match mandatory 'date' format standard ('Y-m-d').",
            ],
            'field_dynamic_rows_missing_columns'        => [
                'dirtyInput'      => array_replace_recursive($baseStructure, [
                    'sections' => [
                        'payment_gateways' => [
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => [
                                        'matrix' => ['type' => 'dynamic_rows'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
                'expectedMessage' => "Field 'columns' for dynamic rows field 'matrix' must be a defined configuration array.",
            ],
            'field_depends_on_non_existent_parent_field' => [
                'dirtyInput'      => array_replace_recursive($baseStructure, [
                    'sections' => [
                        'payment_gateways' => [
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => [
                                        'api_key' => [
                                            'type'    => 'text',
                                            'depends' => ['wrong_parent_field' => 1],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
                'expectedMessage' => "Field 'api_key' depends on an undefined parent field 'wrong_parent_field' within the same group.",
            ],
        ];
    }
}