# Configuration Processor Component

![Tests](https://github.com/Modestox/config-processor/actions/workflows/phpunit.yml/badge.svg)

A standalone PHP library that validates, normalizes and sorts hierarchical configuration arrays, in the spirit of Magento's `system.xml`: `Tabs -> Sections -> Groups -> Fields`.

You pass a raw array and a schema. You get back either a clean, fully populated array or an `InvalidConfigException` describing the first problem found.

## Requirements

- PHP 8.3 or newer
- No runtime dependencies

## Installation

```bash
composer require modestox/config-processor
```

## Features

- **Strict validation** — values are never coerced: `"10"` is not accepted where an integer is expected.
- **Normalization** — strings are trimmed, missing properties are backfilled with defaults at every level.
- **Sorting** — nodes are ordered by `sort_order` (stable, so equal values keep their declaration order).
- **Field dependencies** — `depends` is checked against sibling fields, including self-references and cycles.
- **Extensible** — custom field types can be registered at runtime.
- **Side-effect free** — the input array is never modified; the library has no I/O.

## Why?

Configuration systems often mix validation, storage and rendering in one layer. This library only validates and normalizes the structure. Rendering and persistence are left to adapters built on top of the normalized output.

## Supported Schemas

### System Configuration (`SystemConfig`)

```text
Tabs
└── Sections
    └── Groups
        └── Fields
```

For applications, CMS platforms and admin panels with a full settings tree.

### Grouped Configuration (`GroupedConfig`)

```text
Groups
└── Fields
```

For isolated plugins, widgets and small modules that need neither tabs nor sections. The `groups` key is required.

## Quick Start

### 1. Raw configuration

Note the redundant whitespace and the unsorted `sort_order` values:

```php
$rawInput = [
    'tabs' => [
        'payment_tab' => [
            'label'      => 'Sales & Payments',
            'sort_order' => 20,
        ],
        'main_tab' => [
            'label'      => '  General Settings  ', // Surrounding whitespace is trimmed
            'sort_order' => 10,                     // Sorted first
        ],
    ],
    'sections' => [
        'api_integration' => [
            'tab'        => 'main_tab',
            'label'      => 'API Core Integration',
            'sort_order' => 5,
            'groups'     => [
                'auth_group' => [
                    'label'  => 'Authentication Keys',
                    'fields' => [
                        'enable_api' => [
                            'type' => 'yes_no', // Yes/No options are generated automatically
                        ],
                        'api_secret' => [
                            'type'    => 'password',
                            'default' => '   secret-token-key   ',
                            'depends' => [
                                'enable_api' => 1, // Shown only when API is enabled
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

### 2. Processing

```php
use Modestox\ConfigProcessor\Processor;
use Modestox\ConfigProcessor\Schema\SystemConfig;

$processor = new Processor();

// For isolated plugin structures use GroupedConfig instead:
// $schema = new \Modestox\ConfigProcessor\Schema\GroupedConfig();
$schema = new SystemConfig();

$cleanConfig = $processor->process($rawInput, $schema);
```

### 3. Result

```php
[
    'tabs' => [
        'main_tab' => [
            'label'      => 'General Settings',
            'sort_order' => 10,
            'class'      => '',
            'icon'       => '',
        ],
        'payment_tab' => [
            'label'      => 'Sales & Payments',
            'sort_order' => 20,
            'class'      => '',
            'icon'       => '',
        ],
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
                            'options'    => [0 => 'No', 1 => 'Yes'],
                            'default'    => 0,
                            'comment'    => '',
                            'class'      => '',
                            'validation' => [],
                            'required'   => false,
                            'provider'   => '',
                            'depends'    => null,
                        ],
                        'api_secret' => [
                            'type'       => 'password',
                            'label'      => 'api_secret',
                            'sort_order' => 0,
                            'default'    => 'secret-token-key',
                            'comment'    => '',
                            'class'      => '',
                            'validation' => [],
                            'required'   => false,
                            'provider'   => '',
                            'depends'    => ['enable_api' => 1],
                        ],
                    ],
                ],
            ],
        ],
    ],
]
```

## Structure Reference

| Level | Property | Type | Notes |
| :--- | :--- | :--- | :--- |
| Tab | `label` | string | Defaults to the tab id. |
| | `sort_order` | int | Defaults to `0`. |
| | `class`, `icon` | string | Default to `''`. |
| Section | `tab` | string | **Required.** Must reference a defined tab. |
| | `label`, `sort_order`, `class`, `icon` | | As for tabs. |
| | `groups` | array | Defaults to `[]`. |
| Group | `label`, `sort_order` | | As for tabs. |
| | `fields` | array | Defaults to `[]`. |
| Field | `type` | string | **Required.** See [field types](fields.md). |

**Identifiers.** Tab, section, group and field ids (the array keys) must match `^[a-z0-9_]+$`. Ids made only of digits (e.g. `'123'`) are not supported: PHP turns such keys into integers.

## Validation Errors

The first violation stops processing and throws `Modestox\ConfigProcessor\Exception\InvalidConfigException`. The message names the offending node, but not its full path in the tree.

```php
'cache_timeout' => [
    'type' => 'number',
    'min'  => 100,
    'max'  => 10, // max is less than min
]
```

```text
Modestox\ConfigProcessor\Exception\InvalidConfigException:
Field 'max' cannot be less than 'min' for number field 'cache_timeout'.
```

Programmer errors (for example registering an invalid custom validator) throw `\InvalidArgumentException` instead.

## Behavior Notes

- **Unknown keys are ignored.** A misspelled property (`'defualt'`) or root key (`'tab'` instead of `'tabs'`) is dropped silently and does not produce an error.
- **`null` means "not set".** `'label' => null` is treated like a missing label.
- **Output is not escaped.** Labels, comments and `infoblock` text are returned as given. Escape them when rendering.
- **`depends`** works only between fields of the same group. It does not check that the expected value exists in the parent field.
- **`provider`** is stored as a string. It is neither validated as a class name nor resolved.

## Supported Field Types

| Type (`type`) | Behavior |
| :--- | :--- |
| `text`, `textarea` | Text values; `default` and `placeholder` are trimmed. |
| `password` | Text value; `default` is trimmed. |
| `boolean` | `default` must be a real `true`/`false`; no conversion is performed. |
| `yes_no` | Select with fixed options `[0 => 'No', 1 => 'Yes']`. |
| `number` | `int`/`float` only; finite; `min <= default <= max`. |
| `datetime` | `datetime` (`Y-m-d H:i:s`), `date` (`Y-m-d`) or `time` (`H:i:s`) with real calendar validation. |
| `dynamic_rows` | Table with declared columns; default rows are normalized to the column set. |
| `file`, `image` | Declarative `upload_dir` and `extensions`; enforced by the consuming application. |
| `select`, `radio` | Single choice; `default` must be one of the options. |
| `multiselect`, `checkbox` | Multiple choice; `default` is a list of valid option keys. |
| `infoblock` | Non-data notice with `text` or `html` format. |

Full property reference: [fields.md](fields.md).

## Custom Field Types

Implement `FieldValidatorInterface` and register it. Type names must match `^[a-z0-9_]+$`.

```php
use Modestox\ConfigProcessor\Schema\SystemConfig;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields;
use Modestox\ConfigProcessor\Validator\SystemConfig\Groups;
use Modestox\ConfigProcessor\Validator\SystemConfig\Sections;

$fields = new Fields();
$fields->registerType('colorpicker', new MyColorPickerValidator());

$schema = new SystemConfig(sections: new Sections(new Groups($fields)));
```

## Development

```bash
composer install
composer test
```

## License

MIT. See [LICENSE](LICENSE).
