# Configuration Processor Component

A standalone PHP component designed for multi-level validation, sanitization, normalization, and sorting of hierarchical system configurations (e.g., application module settings, payment gateways, theme configurations, or plugins).

The architecture is built on a flexible, declarative principle of multi-level data grouping: `Tabs -> Sections -> Groups -> Fields`.

Strictly implemented according to the **PHP 8.3+** standard, fully compatible with DI containers, and supports dynamic extensibility with custom field types.

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

| Field Type (`type`) | Type-Specific Properties | Default Behavior & Fallbacks |
| :--- | :--- | :--- |
| **`text`** / **`textarea`** | `default` *(string)*, `placeholder` *(string)* | String fields. All values are automatically sanitized using `trim()`. |
| **`password`** | `default` *(string)* | Masked input field for sensitive data. Default value is trimmed. |
| **`boolean`** | `default` *(bool)* | Logical toggle. Coerces the provided default to a strict `true`/`false`. |
| **`yes_no`** | `default` *(int)* | Yes/No dropdown selection. Automatically injects `options => [0 => 'No', 1 => 'Yes']`. |
| **`number`** | `default`, `min`, `max` *(int/float)* | Numeric field. Enforces strict types and logical boundaries (`max >= min`). |
| **`datetime`** | `view_mode` *(string)*, `default` *(string)* | Validates whether the given string matches `Y-m-d`, `Y-m-d H:i:s`, or `H:i:s` formats. |
| **`dynamic_rows`**| `columns` *(array)*, `default` *(array)* | Two-dimensional table matrix. The structure definition array (`columns`) is required. |
| **`file`** / **`image`** | `upload_dir` *(string)*, `extensions` *(string)* | Declarative rules. Trims path strings. Returns empty strings `''` by default. |
| **`select`** / **`radio`** | `options` *(array)*, `default` *(string/int)* | Single choice from a mandatory, non-empty `options` associative array. |
| **`multiselect`** / **`checkbox`**| `options` *(array)*, `default` *(array)* | Multiple choice. The default value must be a strict array of existing keys. |

> **Full Property Documentation:** A comprehensive breakdown of each field type, its constraints, inheritance, and validation rules has been moved to a separate reference file: [Detailed Field Specifications](fields.md).

---

## Component Extension (Registry)

The component supports runtime registration of new field types without altering the core codebase, allowing it to seamlessly integrate with your application's DI containers:

```php
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields;

$fieldsValidator = new Fields();
// Register a custom validation strategy for a new 'colorpicker' type
$fieldsValidator->registerType('colorpicker', new MyCustomColorPickerValidator());
```

---