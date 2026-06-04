# Configuration Processor Component

![Tests](https://github.com/Modestox/config-processor/actions/workflows/ci.yml/badge.svg)

A standalone PHP component designed for multi-level validation, sanitization, normalization, and sorting of hierarchical system configurations (e.g., application module settings, payment gateways, theme configurations, or plugins).

The architecture is built on a declarative principle of multi-level data grouping: `Tabs -> Sections -> Groups -> Fields`.

---

## Features

- **PHP 8.3+** — Implemented strictly according to the modern language standards.
- **Strict typing** — Native type safety enforced across all validators and configuration nodes.
- **Deep validation** — Comprehensive evaluation of structural constraints, value boundaries, and formats.
- **Recursive normalization** — Automated sanitization and fallback backfilling at every level of the tree.
- **Automatic sorting** — Enforces execution and rendering order using the `sort_order` attribute.
- **Dependency-aware fields** — Supports conditional visibility constraints between fields inside the same group.
- **Dynamic field registry** — Allows runtime registration or overriding of custom field type strategies.
- **Non-destructive processing** — The input configuration array is never modified or passed by reference.
- **DI-container friendly** — Built with low-coupling architecture ready for dependency injection environments.

---

### Configuration Tree Example

This structural hierarchy allows you to map complex nested data models seamlessly:

```text
Tabs
├── General (Section)
│   ├── API Settings (Group)
│   │   ├── Authentication (Sub-context)
│   │   │   ├── enable_api (Field)
│   │   │   └── api_secret (Field)
│   │   └── Cache (Group)
│   └── Security (Group)
│
└── Payments (Section)
    └── Stripe (Group)
```

---

## Quick Start

### 1. Installation

```bash
composer require modestox/config-processor
```

### 2. Raw Configuration Example

The component accepts a raw multi-dimensional array compiled from module configuration files. Note the redundant whitespaces in string values and the unsorted `sort_order` properties:

```php
$rawInput = [
    'tabs' => [
        'payment_tab' => [
            'label'      => 'Sales & Payments',
            'sort_order' => 20,
        ],
        'main_tab' => [
            'label'      => '  General Settings  ', // Contains surrounding whitespaces
            'sort_order' => 10, // Should be sorted first
        ]
    ],
    'sections' => [
        'api_integration' => [
            'tab'        => 'main_tab',
            'label'      => 'API Core Integration',
            'sort_order' => 5,
            'groups'     => [
                'auth_group' => [
                    'label'      => 'Authentication Keys',
                    'fields'     => [
                        'enable_api' => [
                            'type' => 'yes_no', // Automatically generates Yes/No options
                        ],
                        'api_secret' => [
                            'type'       => 'password',
                            'default'    => '   secret-token-key   ', // Surrounding whitespaces will be trimmed
                            'depends'    => [
                                'enable_api' => 1 // Only displayed if API is enabled
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
];
```

### 3. Processing Configuration

Pass the input data array and the validation schema instance to the orchestrator:

```php
use Modestox\ConfigProcessor\Processor;
use Modestox\ConfigProcessor\Schema\SystemConfig;

$processor = new Processor();
$schema = new SystemConfig();

/** @var array $cleanConfig */
$cleanConfig = $processor->process($rawInput, $schema);
```

### 4. Output of the `process()` Method

The method returns a **sanitized associative array (`array`)**, where string parameters are normalized, missing properties are populated with system defaults, and elements across all levels are strictly sorted by their `sort_order` values:

```php
[
    'tabs' => [
        'main_tab' => [
            'label'      => 'General Settings', // Trimmed and sorted to the first position
            'sort_order' => 10,
            'class'      => '',
            'icon'       => ''
        ],
        'payment_tab' => [
            'label'      => 'Sales & Payments',
            'sort_order' => 20,
            'class'      => '',
            'icon'       => ''
        ]
    ],
    'sections' => [
        'api_integration' => [
            'tab'        => 'main_tab',
            'label'      => 'API Core Integration',
            'sort_order' => 5,
            'class'      => '',
            'icon'       => '',
            'groups'     => [
                'auth_group' => [
                    'label'      => 'Authentication Keys',
                    'sort_order' => 0,
                    'fields'     => [
                        'enable_api' => [
                            'type'       => 'yes_no',
                            'label'      => 'enable_api',
                            'sort_order' => 0,
                            'options'    => [0 => 'No', 1 => 'Yes'], // Generated automatically
                            'default'    => 0,
                            'comment'    => '',
                            'class'      => '',
                            'validation' => [],
                            'required'   => false,
                            'depends'    => null
                        ],
                        'api_secret' => [
                            'type'       => 'password',
                            'label'      => 'api_secret',
                            'sort_order' => 0,
                            'default'    => 'secret-token-key', // Trimmed
                            'comment'    => '',
                            'class'       => '',
                            'validation' => [],
                            'required'   => false,
                            'depends'    => ['enable_api' => 1]
                        ]
                    ]
                ]
            ]
        ]
    ]
]
```

---

## Validation Errors

When an invalid structure is detected or field type rules are violated, the processor throws a domain-specific `InvalidConfigException`.

**Example of an invalid field configuration:**

```php
'cache_timeout' => [
    'type' => 'number',
    'min'  => 100,
    'max'  => 10, // Error: max is less than min
]
```

**Execution Result:**

```text
Modestox\ConfigProcessor\Exception\InvalidConfigException:
Field 'max' cannot be less than 'min' for number field 'cache_timeout'.
```

---

## Supported Field Types Summary

The component natively supports a wide range of standard data types and UI hints:

| Field Type (`type`) | Behavior & Fallbacks |
| :--- | :--- |
| **`text`** / **`textarea`** | Standard text data. All values are automatically sanitized using `trim()`. |
| **`password`** | Masked input field for sensitive credentials. Values are automatically trimmed. |
| **`boolean`** | Enforces strict boolean (`true`/`false`) conversion and fallback values. |
| **`yes_no`** | Fast binary dropdown. Automatically injects strict options: `[0 => 'No', 1 => 'Yes']`. |
| **`number`** | Enforces strict numeric types (int/float) and logical validation boundaries (`max >= min`). |
| **`datetime`** | Strictly validates temporal values against ISO standards (`date`, `time`, or `datetime`). |
| **`dynamic_rows`** | Two-dimensional table matrix. Automatically normalizes and backfills nested cell structures. |
| **`file`** / **`image`** | Declarative mapping for paths and extensions handled by the application runtime. |
| **`select`** / **`radio`** | Single choice options picker. Strictly validates default keys against predefined scope. |
| **`multiselect`** / **`checkbox`**| Multiple choice options picker. Enforces default data to be an array of valid keys. |
| **`infoblock`** | Non-data text placeholder. Defines how the content should be interpreted by the consuming application. |

> **Full Property Documentation:** A comprehensive breakdown of each field type, its constraints, inheritance, and validation rules can be found in the separate reference file: [Detailed Field Specifications](fields.md).

---

## Component Extension (Registry)

The component supports runtime registration of new field types without altering the core codebase, allowing it to seamlessly integrate with your application's DI containers:

```php
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields;

$fieldsValidator = new Fields();
// Register a custom validation strategy for a new 'colorpicker' type
$fieldsValidator->registerType('colorpicker', new MyCustomColorPickerValidator());
```