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
            'tabs' => [
                'modestox_modules' => [
                    'label'      => 'Modestox CMS Modules',
                    'sort_order' => 20,
                    'class'      => 'cms-tab',
                    'icon'       => 'fa fa-cubes',
                ],
                'payment_tab' => [
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
                    'groups' => [
                        'basic_group' => [
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
                                ],
                                'site_name' => [
                                    'type'       => 'text',
                                    'label'      => 'Site Title',
                                    'sort_order' => 10,
                                    'default'    => 'Modestox Store',
                                ],
                                'meta_description' => [
                                    'sort_order' => 20,
                                    'type'       => 'text',
                                ],
                                'enable_cache' => [
                                    'type'       => 'boolean',
                                    'label'      => 'Enable System Cache',
                                    'sort_order' => 5,
                                    'comment'    => '  Recommended for production speed.  ',
                                    'class'      => 'toggle-cache',
                                ],
                                'maintenance_mode' => [
                                    'type'       => 'boolean',
                                ],
                            ],
                        ],
                        'advanced_group' => [
                            'label'      => 'Advanced Infrastructure Cryptography',
                            'sort_order' => 10,
                            'fields'     => [
                                'api_secret_token' => [
                                    'type'       => 'text',
                                    'label'      => 'API Secret Token',
                                    'sort_order' => 100,
                                ],
                                'allowed_countries' => [ // New Valid Multiselect Field
                                    'type'       => 'multiselect',
                                    'label'      => 'Shipment Countries',
                                    'sort_order' => 10,
                                    'default'    => ['DE', 'CH'],
                                    'comment'    => '  Select multiple target locations.  ',
                                    'class'      => 'chosen-select',
                                    'options'    => [
                                        'DE' => 'Germany',
                                        'AT' => 'Austria',
                                        'CH' => 'Switzerland',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'advanced_features' => [
                    'tab'        => 'modestox_modules',
                    'label'      => 'Advanced Features',
                    'sort_order' => 5,
                ],
            ],
        ];

        $expectedOutput = [
            'tabs' => [
                'payment_tab' => [
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
                'advanced_features' => [
                    'tab'        => 'modestox_modules',
                    'label'      => 'Advanced Features',
                    'sort_order' => 5,
                    'groups'     => [],
                ],
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
                                'allowed_countries' => [
                                    'type'       => 'multiselect',
                                    'label'      => 'Shipment Countries',
                                    'sort_order' => 10,
                                    'default'    => ['DE', 'CH'],
                                    'comment'    => 'Select multiple target locations.',
                                    'class'      => 'chosen-select',
                                    'options'    => [
                                        'DE' => 'Germany',
                                        'AT' => 'Austria',
                                        'CH' => 'Switzerland',
                                    ],
                                ],
                                'api_secret_token' => [
                                    'type'        => 'text',
                                    'label'       => 'API Secret Token',
                                    'sort_order'  => 100,
                                    'default'     => '',
                                    'placeholder' => '',
                                    'comment'     => '',
                                    'class'       => '',
                                    'validation'  => [],
                                ],
                            ],
                        ],
                        'basic_group' => [
                            'label'      => 'Basic System Core Configurations',
                            'sort_order' => 30,
                            'fields'     => [
                                'enable_cache' => [
                                    'type'       => 'boolean',
                                    'label'      => 'Enable System Cache',
                                    'sort_order' => 5,
                                    'default'    => false,
                                    'comment'    => 'Recommended for production speed.',
                                    'class'      => 'toggle-cache',
                                ],
                                'maintenance_mode' => [
                                    'type'       => 'boolean',
                                    'label'      => 'maintenance_mode',
                                    'sort_order' => 0,
                                    'default'    => false,
                                    'comment'    => '',
                                    'class'       => '',
                                ],
                                'site_name' => [
                                    'type'        => 'text',
                                    'label'       => 'Site Title',
                                    'sort_order'  => 10,
                                    'default'     => 'Modestox Store',
                                    'placeholder' => '',
                                    'comment'     => '',
                                    'class'       => '',
                                    'validation'  => [],
                                ],
                                'meta_description' => [
                                    'type'        => 'text',
                                    'label'       => 'meta_description',
                                    'sort_order'  => 20,
                                    'default'     => '',
                                    'placeholder' => '',
                                    'comment'     => '',
                                    'class'       => '',
                                    'validation'  => [],
                                ],
                                'google_analytics_id' => [
                                    'type'        => 'text',
                                    'label'       => 'Google Analytics ID',
                                    'sort_order'  => 50,
                                    'default'     => '',
                                    'placeholder' => 'UA-XXXXX-Y',
                                    'comment'     => 'Enter your production tracking code.',
                                    'class'       => 'input-analytics',
                                    'validation'  => ['required', 'min:10'],
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
        return [
            // Tabs validation scenarios
            'tabs_is_not_an_array' => [
                'dirtyInput'      => ['tabs' => 'string'],
                'expectedMessage' => "The 'tabs' section must be an array."
            ],
            'tab_configuration_must_be_array' => [
                'dirtyInput'      => ['tabs' => ['modestox_modules' => 'flat-string']],
                'expectedMessage' => "Configuration for tab 'modestox_modules' must be an array."
            ],
            'sort_order_is_not_numeric' => [
                'dirtyInput'      => [
                    'tabs' => [
                        'modestox_modules' => ['sort_order' => '10']
                    ]
                ],
                'expectedMessage' => "Field 'sort_order' for tab 'modestox_modules' must be a strict integer."
            ],
            // Sections validation scenarios
            'sections_root_is_not_an_array' => [
                'dirtyInput'      => ['sections' => 'broken_string_instead_of_array'],
                'expectedMessage' => "The 'sections' section must be an array."
            ],
            'section_configuration_must_be_array' => [
                'dirtyInput'      => [
                    'tabs' => ['main_tab' => []],
                    'sections' => ['payment_gateways' => 'flat-string-instead-of-nested-array']
                ],
                'expectedMessage' => "Configuration for section 'payment_gateways' must be an array."
            ],
            'section_sort_order_is_not_numeric' => [
                'dirtyInput'      => [
                    'tabs' => ['main_tab' => []],
                    'sections' => [
                        'payment_gateways' => ['tab' => 'main_tab', 'sort_order' => '10']
                    ],
                ],
                'expectedMessage' => "Field 'sort_order' for section 'payment_gateways' must be a strict integer."
            ],
            'section_groups_must_be_an_array' => [
                'dirtyInput'      => [
                    'tabs' => ['main_tab' => []],
                    'sections' => [
                        'payment_gateways' => ['tab' => 'main_tab', 'groups' => 'string-instead-of-array']
                    ]
                ],
                'expectedMessage' => "The 'groups' under section 'payment_gateways' must be an array."
            ],
            // Cross-validation constraint scenarios
            'section_is_missing_mandatory_tab_parameter' => [
                'dirtyInput'      => [
                    'tabs' => ['main_tab' => []],
                    'sections' => [
                        'payment_gateways' => ['sort_order' => 10]
                    ]
                ],
                'expectedMessage' => "Section 'payment_gateways' is missing its mandatory 'tab' assignment parameter."
            ],
            // Groups validation scenarios
            'group_configuration_must_be_an_array' => [
                'dirtyInput'      => [
                    'tabs' => ['main_tab' => []],
                    'sections' => [
                        'payment_gateways' => [
                            'tab'    => 'main_tab',
                            'groups' => ['credentials_group' => 'string_instead_of_array']
                        ]
                    ]
                ],
                'expectedMessage' => "Configuration for group 'credentials_group' must be an array."
            ],
            'group_sort_order_must_be_a_strict_integer' => [
                'dirtyInput'      => [
                    'tabs' => ['main_tab' => []],
                    'sections' => [
                        'payment_gateways' => [
                            'tab'    => 'main_tab',
                            'groups' => [
                                'credentials_group' => ['sort_order' => '5']
                            ]
                        ]
                    ]
                ],
                'expectedMessage' => "Field 'sort_order' for group 'credentials_group' must be a strict integer."
            ],
            'group_fields_must_be_an_array' => [
                'dirtyInput'      => [
                    'tabs' => ['main_tab' => []],
                    'sections' => [
                        'payment_gateways' => [
                            'tab'    => 'main_tab',
                            'groups' => [
                                'credentials_group' => ['fields' => 'string_instead_of_array']
                            ]
                        ]
                    ]
                ],
                'expectedMessage' => "The 'fields' under group 'credentials_group' must be an array."
            ],
            // Fields validation scenarios
            'field_is_missing_mandatory_type_parameter' => [
                'dirtyInput'      => [
                    'tabs' => ['main_tab' => []],
                    'sections' => [
                        'payment_gateways' => [
                            'tab'    => 'main_tab',
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => ['api_key' => ['label' => 'API Key']]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedMessage' => "Field 'api_key' is missing its mandatory 'type' parameter."
            ],
            'field_uses_an_unsupported_type_definition' => [
                'dirtyInput'      => [
                    'tabs' => ['unsupported_tab' => []],
                    'sections' => [
                        'unsupported_section' => [
                            'tab'    => 'unsupported_tab',
                            'groups' => [
                                'unsupported_group' => [
                                    'fields' => [
                                        'unsupported_field' => ['type' => 'wysiwyg'] // Меняем на wysiwyg
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                // Добавляем textarea в список поддерживаемых, а падаем на wysiwyg
                'expectedMessage' => "Field 'unsupported_field' uses an unsupported type 'wysiwyg'. Supported types are: text, boolean, select, multiselect, radio, checkbox, textarea."
            ],
            // Text-field specific validation scenarios
            'field_text_default_must_be_strict_string' => [
                'dirtyInput'      => [
                    'tabs' => ['main_tab' => []],
                    'sections' => [
                        'payment_gateways' => [
                            'tab'    => 'main_tab',
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => ['api_key' => ['type' => 'text', 'default' => true]]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedMessage' => "Field 'default' for text field 'api_key' must be a strict string."
            ],
            'field_text_placeholder_must_be_strict_string' => [
                'dirtyInput'      => [
                    'tabs' => ['main_tab' => []],
                    'sections' => [
                        'payment_gateways' => [
                            'tab'    => 'main_tab',
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => ['api_key' => ['type' => 'text', 'placeholder' => 123]]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedMessage' => "Field 'placeholder' for text field 'api_key' must be a strict string."
            ],
            'field_text_comment_must_be_strict_string' => [
                'dirtyInput'      => [
                    'tabs' => ['main_tab' => []],
                    'sections' => [
                        'payment_gateways' => [
                            'tab'    => 'main_tab',
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => ['api_key' => ['type' => 'text', 'comment' => []]]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedMessage' => "Field 'comment' for text field 'api_key' must be a strict string."
            ],
            'field_text_class_must_be_strict_string' => [
                'dirtyInput'      => [
                    'tabs' => ['main_tab' => []],
                    'sections' => [
                        'payment_gateways' => [
                            'tab'    => 'main_tab',
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => ['api_key' => ['type' => 'text', 'class' => false]]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedMessage' => "Field 'class' for text field 'api_key' must be a valid string."
            ],
            'field_text_validation_must_be_an_array' => [
                'dirtyInput'      => [
                    'tabs' => ['main_tab' => []],
                    'sections' => [
                        'payment_gateways' => [
                            'tab'    => 'main_tab',
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => ['api_key' => ['type' => 'text', 'validation' => 'required']]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedMessage' => "Field 'validation' for text field 'api_key' must be an array of rule strings."
            ],
            'field_text_validation_rule_must_be_strict_string' => [
                'dirtyInput'      => [
                    'tabs' => ['main_tab' => []],
                    'sections' => [
                        'payment_gateways' => [
                            'tab'    => 'main_tab',
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => ['api_key' => ['type' => 'text', 'validation' => ['required', 456]]]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedMessage' => "Validation rule at index 1 for text field 'api_key' must be a strict string."
            ],
            // Boolean-field specific validation scenarios
            'field_boolean_default_must_be_strict_bool' => [
                'dirtyInput'      => [
                    'tabs' => ['main_tab' => []],
                    'sections' => [
                        'payment_gateways' => [
                            'tab'    => 'main_tab',
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => ['enable_feature' => ['type' => 'boolean', 'default' => 'true']]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedMessage' => "Field 'default' for boolean field 'enable_feature' must be a strict boolean value."
            ],
            // New Multiselect-field specific validation scenarios
            'field_multiselect_default_must_be_strict_array' => [
                'dirtyInput'      => [
                    'tabs' => ['main_tab' => []],
                    'sections' => [
                        'payment_gateways' => [
                            'tab'    => 'main_tab',
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => [
                                        'allowed_currencies' => [
                                            'type'    => 'multiselect',
                                            'default' => 'USD', // String instead of array
                                            'options' => ['USD' => 'US Dollar']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedMessage' => "Field 'default' for multiselect field 'allowed_currencies' must be a strict array of keys."
            ],
            'field_multiselect_default_key_must_exist_in_options' => [
                'dirtyInput'      => [
                    'tabs' => ['main_tab' => []],
                    'sections' => [
                        'payment_gateways' => [
                            'tab'    => 'main_tab',
                            'groups' => [
                                'credentials_group' => [
                                    'fields' => [
                                        'allowed_currencies' => [
                                            'type'    => 'multiselect',
                                            'default' => ['EUR'], // Key doesn't exist in options pool
                                            'options' => ['USD' => 'US Dollar']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedMessage' => "The default value 'EUR' for field 'allowed_currencies' does not exist within the permitted options scope."
            ]
        ];
    }
}