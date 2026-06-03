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

// Dirty data coming from configuration inputs with extra spaces
$rawInput = [
    'tabs' => [
        'marketing_tab' => [
            'label' => 'Marketing Settings',
            'sort_order' => 10
        ]
    ],
    'sections' => [
        'promotions' => [
            'tab' => 'marketing_tab',
            'label' => 'Store Promotions',
            'sort_order' => 10,
            'groups' => [
                'discounts' => [
                    'label' => 'Active Discounts',
                    'sort_order' => 5,
                    'fields' => [
                        'promo_start' => [
                            'type'      => 'datetime',
                            'view_mode' => 'datetime', // Mandatory mode for time granularity
                            'label'     => 'Start Date and Time',
                            'default'   => '   2026-11-25 00:00:00   ', // Will be trimmed automatically
                        ],
                        'promo_end' => [
                            'type'      => 'datetime',
                            'view_mode' => 'date', // Mandatory mode for day-only granularity
                            'label'     => 'End Date',
                            'default'   => '2026-12-31', // Strictly checked against Y-m-d standard
                        ]
                    ]
                ]
            ]
        ]
    ]
];

// Processing will normalize fields and automatically sanitize string values
$cleanConfig = $processor->process($rawInput, $schema);

print_r($cleanConfig);