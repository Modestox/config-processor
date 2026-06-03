<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Modestox\ConfigProcessor\Processor;
use Modestox\ConfigProcessor\Schema\SystemConfig;

$processor = new Processor();
$schema = new SystemConfig();

// Matrix table configuration with nested raw rows
$rawInput = [
    'tabs' => [
        'sales_tab' => [
            'label' => 'Sales Configuration',
            'sort_order' => 30
        ]
    ],
    'sections' => [
        'shipping_methods' => [
            'tab' => 'sales_tab',
            'label' => 'Shipping Rates',
            'sort_order' => 20,
            'groups' => [
                'table_rate' => [
                    'label' => 'Weight Rates Mapping',
                    'sort_order' => 10,
                    'fields' => [
                        'weight_cost_matrix' => [
                            'type'    => 'dynamic_rows',
                            'label'   => 'Shipping Matrix Table',
                            'sort_order' => 100,
                            // Columns definition is strictly mandatory for dynamic rows
                            'columns' => [
                                'max_weight' => 'Maximum Weight Limit (kg)',
                                'price'      => 'Delivery Flat Rate (EUR)'
                            ],
                            // Multi-dimensional default array with inner trailing spaces to be cleaned
                            'default' => [
                                [
                                    'max_weight' => '   5   ',
                                    'price'      => '   4.90   '
                                ],
                                [
                                    'max_weight' => '10',
                                    'price'      => '8.50'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
];

// Processing sanitizes deep inner cells iteratively using matrix validation rules
$cleanConfig = $processor->process($rawInput, $schema);

print_r($cleanConfig);