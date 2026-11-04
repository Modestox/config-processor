# Config Processor

**English** | [Русский](README.ru.md) | [Deutsch](README.de.md)

![Tests](https://github.com/Modestox/config-processor/actions/workflows/phpunit.yml/badge.svg)

A PHP library that validates and normalizes an array describing settings. The format is inspired by Magento's `system.xml`: `Tabs -> Sections -> Groups -> Fields`.

The input is a "raw" array and a schema. The output is either a clean array with a uniform, predictable shape, or an `InvalidConfigException` with the exact path to the error.

```text
raw array  ──►  Processor + Schema  ──►  clean array
                      │
                      └──►  InvalidConfigException (path + reason)
```

## Contents

- [Why this exists](#why-this-exists)
- [Requirements and installation](#requirements-and-installation)
- [Quick start](#quick-start)
- [How it works](#how-it-works)
- [Level reference](#level-reference)
- [Core rules](#core-rules)
- [Errors](#errors)
- [Field dependencies (`depends`)](#field-dependencies-depends)
- [Field types](#field-types)
- [Custom field types](#custom-field-types)
- [What the library does not do](#what-the-library-does-not-do)
- [Repository layout](#repository-layout)
- [Development and tests](#development-and-tests)
- [Upgrading from 1.x to 2.0](#upgrading-from-1x-to-20)

---

## Why this exists

A module's admin settings (like `system.xml` in Magento) are convenient to describe declaratively: tabs, sections, groups, fields. But when a human writes such a description, it ends up with typos, stray whitespace, missing keys, and fields that point to fields that do not exist.

The library solves one task: **validate the description and return it in a normalized form**.

The main idea behind the design: **every key is always present in the output**. If you did not set `sort_order`, you get `0`. If you did not set `class`, you get `''`. So the code that later renders a form or saves settings needs no `isset` checks and does not break on a missing key.

What the library does **not** do: it does not render a form, does not store values, does not read `$_POST`. It validates only the *description* of the settings. This matters, see [What the library does not do](#what-the-library-does-not-do).

## Requirements and installation

- PHP 8.3 or newer
- No dependencies

```bash
composer require modestox/config-processor
```

## Quick start

### 1. Raw description

Note the stray whitespace and the unordered `sort_order` values:

```php
$rawInput = [
    'tabs' => [
        'payment_tab' => [
            'label'      => 'Sales & Payments',
            'sort_order' => 20,
        ],
        'main_tab' => [
            'label'      => '  General Settings  ', // edge whitespace will be trimmed
            'sort_order' => 10,                      // this tab will come first
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
                            'type' => 'yes_no', // Yes/No options are created automatically
                        ],
                        'api_secret' => [
                            'type'    => 'password',
                            'default' => '   secret-token-key   ',
                            'depends' => [
                                'enable_api' => 1, // visible only if the API is enabled
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

// For small plugins without tabs and sections:
// $schema = new \Modestox\ConfigProcessor\Schema\GroupedConfig();
$schema = new SystemConfig();

$cleanConfig = $processor->process($rawInput, $schema);
```

### 3. Result

All values are normalized, all keys are present, everything is sorted by `sort_order`:

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

### Typical scenario

A module keeps its description in its own file and processes it once:

```php
// etc/config.php of the module
return [
    'tabs'     => [ /* ... */ ],
    'sections' => [ /* ... */ ],
];
```

```php
$raw   = require __DIR__ . '/etc/config.php';
$clean = (new Processor())->process($raw, new SystemConfig());

// $clean can now be cached and handed to a renderer without repeated checks.
```

## How it works

`Processor` validates nothing itself, it passes the data to a schema. The schema hands work to the level validators, and the field validator picks the right type:

```text
Processor::process($raw, $schema)
└── SystemConfig::validate()          checks the root keys
    ├── Tabs::validate()              tabs
    └── Sections::validate()          sections (know the list of tabs)
        └── Groups::validate()        groups
            └── Fields::validate()    fields: type, dependencies
                └── Text / Select / Number / ...   rules of the concrete type
```

Tabs, Sections, Groups and Fields extend a shared `AbstractNodeValidator`. It does the work that is the same for all of them: checks the identifier, checks unknown keys, handles `label` and `sort_order`, sorts the result and adds the path to the error. A concrete validator describes only what makes its level different.

Two ready-made schemas:

| Schema | Structure | When to use |
| :--- | :--- | :--- |
| `SystemConfig` | Tabs → Sections → Groups → Fields | Large modules and admin panels |
| `GroupedConfig` | Groups → Fields (the `groups` key is required) | Small plugins and widgets |

## Level reference

| Level | Key | Type | What happens to it |
| :--- | :--- | :--- | :--- |
| `SystemConfig` root | `tabs`, `sections` | array | Both optional. |
| `GroupedConfig` root | `groups` | array | Required. |
| Tab | `label` | string | Defaults to the tab id. |
| | `sort_order` | int | Defaults to `0`. |
| | `class`, `icon` | string | Default to `''`. |
| Section | `tab` | string | **Required.** Must reference a defined tab. |
| | `label`, `sort_order`, `class`, `icon` | | Same as for a tab. |
| | `groups` | array | Defaults to `[]`. |
| Group | `label`, `sort_order` | | Same as for a tab. |
| | `fields` | array | Defaults to `[]`. |
| Field | `type` | string | **Required.** See [field types](#field-types). |
| | the rest | | Common keys and type-specific keys, see [fields.md](fields.md). |

## Core rules

### 1. Identifiers

Keys of tabs, sections, groups and fields must match `^[a-z0-9_]+$`: lower-case Latin letters, digits and underscore.

```php
'site_name'   // good
'Site Name'   // error: upper case and a space
'site-name'   // error: hyphen
"site_name\n" // error: trailing newline
'123'         // error: PHP turns such a key into an integer
```

There is a subtlety with keys made only of digits: PHP automatically makes `'123'` an integer. The library cannot tell `'123'` from `123`, so such ids are not supported. `'item_123'` works.

### 2. Strict types, no coercion

The library does not convert anything "kindly". The string `"10"` is not accepted where a number is needed, `1` is not accepted where `true` is needed:

```php
'cache_ttl'   => ['type' => 'number', 'default' => '10']  // error: int or float required
'maintenance' => ['type' => 'boolean', 'default' => 1]    // error: true or false required
```

The only deliberate conversion: numbers in `dynamic_rows` cells become strings (`5` → `'5'`) so that all cells have the same type.

### 3. Everything not specified is filled in

```php
'site_name' => ['type' => 'text']
```

becomes

```php
'site_name' => [
    'type' => 'text', 'label' => 'site_name', 'sort_order' => 0,
    'default' => '', 'placeholder' => '', 'comment' => '', 'class' => '',
    'validation' => [], 'required' => false, 'provider' => '', 'depends' => null,
]
```

If a `number` has `min` but no `default`, the default value is not `0` but the nearest bound of the range:

```php
['type' => 'number', 'min' => 5]  // default becomes 5, not 0
```

### 4. Unknown keys are an error

A typo in a key must not vanish silently. If you write `defualt` instead of `default`, the value would be lost and you would find out only on the settings page. Now the library stops immediately and suggests a fix:

```text
sections.api.groups.auth.fields.site_name: Unknown key 'defualt' in field 'site_name'.
Did you mean 'default'? Allowed keys: type, label, sort_order, depends, comment, class,
validation, required, provider, default, placeholder. Custom keys must start with 'x_'.
```

The same at the root level:

```text
Unknown key 'tab' in the configuration root. Did you mean 'tabs'? Allowed keys: tabs, sections.
Custom keys must start with 'x_'.
```

The allowed keys depend on the field type. `placeholder` on a `select` or `options` on a `yes_no` are also unknown keys.

#### Custom parameters: the `x_` prefix

If a module needs its own parameter (for example, a model class for a data source), name it with the `x_` prefix. Such keys are allowed on any level and go into the result **as is, without validation**:

```php
'country' => [
    'type'           => 'select',
    'options'        => ['de' => 'Germany'],
    'x_source_model' => 'My\\Module\\Model\\Countries', // goes into the result unchanged
],
```

Format: `x_`, then lower-case letters, digits and underscores. `x_`, `X_custom`, `x_my-key` are not valid.

### 5. `null` means "not set"

```php
'label' => null  // same as removing the key: label becomes equal to the id
```

This is convenient for arrays built programmatically.

### 6. Sorting

Nodes of each level are ordered by `sort_order` ascending. The sort is **stable**: nodes with the same `sort_order` keep the order in which they were described.

## Errors

The first error found stops processing and throws `Modestox\ConfigProcessor\Exception\InvalidConfigException`. The message starts with the path to the problem:

```text
sections.api.groups.auth.fields.cache_timeout: Field 'max' cannot be less than 'min' for number field 'cache_timeout'.
```

The path is needed because identical field ids can exist in different groups.

```php
use Modestox\ConfigProcessor\Exception\InvalidConfigException;

try {
    $clean = $processor->process($raw, new SystemConfig());
} catch (InvalidConfigException $e) {
    echo $e->getPath();    // sections.api.groups.auth.fields.cache_timeout
    echo $e->getReason();  // Field 'max' cannot be less than 'min' for number field 'cache_timeout'.
    echo $e->getMessage(); // path + colon + reason
}
```

If the error is at the very root, the path is empty and `getMessage()` equals `getReason()`.

Programmer errors (for example, registering a wrong type validator) throw a plain `\InvalidArgumentException`.

### Examples: what you wrote and what you got

| What is in the description | Message (after the path) |
| :--- | :--- |
| `'Site Name' => [...]` | `The field identifier key 'Site Name' contains invalid characters. Only lower-case alphanumeric characters and underscores are allowed (a-z, 0-9, _).` |
| `'123' => [...]` | `The field identifier key must be a valid string. Numeric keys are cast to integers by PHP and are not supported.` |
| `'type' => 'colour'` | `Unsupported type 'colour' in field 'f'. Supported: text, boolean, select, ...` |
| a section with `'tab' => 'other'` | `Section 'api' references an undefined parent tab 'other'. Defined tabs: main_tab.` |
| a section when `tabs` is absent | `Section 'api' references an undefined parent tab 'other'. No tabs are defined.` |
| `number`: `min` 100, `max` 10 | `Field 'max' cannot be less than 'min' for number field 'cache_timeout'.` |
| `number`: `'default' => '10'` | `Field 'default' for number field 'n' must be a strict integer or float.` |
| `datetime` `date`: `'2026-02-31'` | `Default value '2026-02-31' for field 'd' does not match mandatory 'date' format standard ('Y-m-d').` |
| `file`: `'upload_dir' => '../etc'` | `Field 'upload_dir' for field 'f' must not contain '..' segments or null bytes.` |
| `dynamic_rows`: extra column in a row | `Row at index '0' inside dynamic rows field 'r' contains unknown column 'zzz'.` |
| `multiselect`: `['DE', 'DE']` | `The default value 'DE' for field 'c' is listed more than once.` |

## Field dependencies (`depends`)

A field can depend on other fields **of the same group**. `depends` holds the id of the parent and the value at which the field must be visible:

```php
'fields' => [
    'mode' => [
        'type'    => 'select',
        'options' => ['live' => 'Live', 'test' => 'Test'],
    ],
    'live_key' => [
        'type'    => 'password',
        'depends' => ['mode' => 'live'],   // visible only if mode = live
    ],
    'debug' => [
        'type'    => 'boolean',
        'depends' => ['mode' => 'test', 'live_key' => 'x'], // several conditions are possible
    ],
],
```

What is checked:

| Situation | Result |
| :--- | :--- |
| The parent is not in the group | Error: `depends on an undefined parent field 'ghost'` |
| A field depends on itself | Cycle error: `a -> a` |
| A chain going in a circle | Cycle error: `a -> b -> a` |
| The parent is an `infoblock` | Error: it holds no value |
| The parent is `select`, `radio`, `yes_no` | The expected value must be one of the `options` keys |
| The parent is `boolean` | The expected value must be `true` or `false` |
| The parent is `number` | The expected value must be `int` or `float` |
| The parent is `text`, `multiselect` and others | The value is not checked |

Example messages:

```text
Field 'key' expects the value "prod" from 'mode', which is not one of its options (live, test).
Field 'key' cannot depend on infoblock 'note': it holds no value.
Field 'key' expects the value 1 from boolean field 'on'; use true or false.
Circular dependency detected between fields: a -> b -> a.
```

The order in which fields are described does not matter: `live_key` may stand in the array before `mode`.

The expected value can only be a scalar (string, number, bool). Specifying several allowed values is not possible at the moment.

## Field types

| Type (`type`) | Purpose |
| :--- | :--- |
| `text`, `textarea` | Text; `default` and `placeholder` are trimmed at the edges. |
| `password` | A secret; `default` is trimmed. |
| `boolean` | `default` is strictly `true` or `false`. |
| `yes_no` | A drop-down with fixed `[0 => 'No', 1 => 'Yes']`. |
| `number` | Only finite `int` and `float`; `min <= default <= max`. |
| `datetime` | `datetime`, `date` or `time` checked against the real calendar. |
| `dynamic_rows` | A table with declared columns. |
| `file`, `image` | Describes upload rules: `upload_dir`, `extensions`. |
| `select`, `radio` | One option from a list; without `default` the first one is taken. |
| `multiselect`, `checkbox` | Several options from a list. |
| `infoblock` | An information block (not an input field). |

Details on every type, its keys, rules and examples: [fields.md](fields.md).

## Custom field types

A custom field type is a class that extends `AbstractFieldValidator`. It does two things: lists **its own** keys in `specificKeys()` and validates values in `validate()`.

```php
use Modestox\ConfigProcessor\Exception\InvalidConfigException;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\AbstractFieldValidator;

final class ColorPicker extends AbstractFieldValidator
{
    /** Keys allowed only for this type (common keys are added automatically). */
    protected function specificKeys(): array
    {
        return ['default', 'palette'];
    }

    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        $default = $this->validateString($fieldData, 'default', $fieldId, '#000000');
        if (!preg_match('/^#[0-9a-f]{6}$/iD', $default)) {
            throw new InvalidConfigException("Field '{$fieldId}' must have a hex color default.");
        }

        $palette = $this->validateString($fieldData, 'palette', $fieldId, 'basic');

        return array_merge(
            $baseMeta,                                  // type, label, sort_order
            ['default' => $default, 'palette' => $palette],
            $this->validateCommon($fieldId, $fieldData) // comment, class, validation, required, provider
        );
    }
}
```

Registering it in a schema:

```php
use Modestox\ConfigProcessor\Processor;
use Modestox\ConfigProcessor\Schema\SystemConfig;

$schema = SystemConfig::withFieldTypes(['colorpicker' => new ColorPicker()]);
// GroupedConfig::withFieldTypes([...]) works the same way

$clean = (new Processor())->process($raw, $schema);
```

Now the description can use `'type' => 'colorpicker'`. A key typo such as `pallete` gives the same `Did you mean 'palette'?` hint as the built-in types.

Rules:

- The type name matches `^[a-z0-9_]+$`.
- A built-in type can be overridden by registering your own `text`, `select`, etc.
- If you do not want to extend `AbstractFieldValidator`, implement `FieldValidatorInterface` in full. Then `allowedKeys()` must return **all** allowed keys, including `type`, `label`, `sort_order`, `depends`.
- Base class helpers: `validateString`, `validateBool`, `validateArray`, `validateOptions`, `validateCommon`.

## What the library does not do

- **It does not validate values entered by users.** It validates the *description* of settings. Form data (`$_POST`) arrives as strings, and strict type checking would reject it. Values need a separate layer.
- **It does not escape text.** `label`, `comment` and the `infoblock` text are returned as is. Escape on output (`htmlspecialchars`), especially for `format => 'html'`.
- **It does not check that folders and files exist.** `upload_dir` is only checked for `..` and null bytes. Applying it is up to your code.
- **It does not load `provider`.** It is just a string with a class name; it is neither checked nor called.
- **It does not collect all errors at once.** It stops at the first one. The path in the message helps to find it quickly.

## Repository layout

```text
src/
├── Processor.php                      entry point
├── Exception/InvalidConfigException   error with a path
├── Schema/
│   ├── SchemaInterface                schema contract
│   ├── SystemConfig                   tabs → sections → groups → fields
│   └── GroupedConfig                  groups → fields
└── Validator/
    ├── ValidatorInterface             level validator contract
    ├── Identifier                     identifier check
    ├── Keys                           unknown keys, x_ and hints
    └── SystemConfig/
        ├── AbstractNodeValidator      shared level logic
        ├── Tabs, Sections, Groups, Fields
        └── Fields/                    field type validators
tests/                                 PHPUnit
fields.md                              field type reference
```

## Development and tests

```bash
composer install
composer test
```

In DDEV: `ddev composer install` and `ddev exec vendor/bin/phpunit`.

## Upgrading from 1.x to 2.0

Version 2.0 is stricter. A config that passed in 1.x may stop passing. What changed:

| What | In 1.x | In 2.0 |
| :--- | :--- | :--- |
| Unknown keys | Silently dropped | Error with a hint; custom keys only with the `x_` prefix |
| `options` on `yes_no` | Silently replaced | Error |
| Error messages | No path | Path at the start, `getPath()` and `getReason()` available |
| `depends` | Only the parent's existence was checked | Also: cycles, values, `infoblock` as a parent |
| Duplicates in `multiselect` `default` | Accepted | Error |
| `default` of `select`/`multiselect` | Left as written (`'1'`) | Cast to the option key type (`1`) |
| Identifier with a trailing `\n` | Accepted (bug) | Error |
| `number` without `default` and `min > 0` | `default = 0` (out of range) | `default = min` |
| `dynamic_rows` | Extra columns silently lost | Error; at least one column required |
| `datetime` with a wrong `view_mode` | Silently replaced by `datetime` | Error |
| `file`: `extensions`, `upload_dir` | Not checked | `jpg,png` format; `..` forbidden |
| Tabs and Sections | No `class` and `icon` if not set | Always present, default `''` |
| `FieldValidatorInterface` | `validate()` only | Also `allowedKeys()` |

Details: [CHANGELOG.md](CHANGELOG.md).

## License

MIT. See [LICENSE](LICENSE).