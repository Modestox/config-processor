# Field type reference

**English** | [Русский](fields.ru.md) | [Deutsch](fields.de.md)

All examples below can be pasted into `fields` of any group. Results are shown in abbreviated form: without the common keys (`label`, `sort_order`, `comment`, `class`, `validation`, `required`, `provider`, `depends`), which are the same for every type and described right below.

## Contents

- [Common properties](#common-properties)
- [`text` and `textarea`](#text-and-textarea)
- [`password`](#password)
- [`boolean`](#boolean)
- [`yes_no`](#yes_no)
- [`number`](#number)
- [`datetime`](#datetime)
- [`dynamic_rows`](#dynamic_rows)
- [`file` and `image`](#file-and-image)
- [`select` and `radio`](#select-and-radio)
- [`multiselect` and `checkbox`](#multiselect-and-checkbox)
- [`infoblock`](#infoblock)
- [Cheat sheet: which keys belong to which type](#cheat-sheet-which-keys-belong-to-which-type)

## Common properties

These keys are accepted by **any** field type.

| Key | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `type` | string | **required** | Field type. Edge whitespace is trimmed. |
| `label` | string | field id | Caption. An empty string or `null` is replaced by the field id. |
| `sort_order` | int | `0` | Order inside the group. The sort is stable. |
| `comment` | string | `''` | Hint below the field. Trimmed. |
| `class` | string | `''` | CSS class. Trimmed. |
| `validation` | array of string | `[]` | List of validation rule names, e.g. `['required-entry']`. Each one is trimmed. The library does not run them, it only passes them on. |
| `required` | bool | `false` | Whether the field is required. Only `true` or `false`. |
| `provider` | string | `''` | Name of a data provider class. Stored as a string and not checked. |
| `depends` | array | `null` | Visibility conditions, see ["Field dependencies" in the README](README.md#field-dependencies-depends). |
| `x_...` | any | | Custom parameters with the `x_` prefix. Go into the result unchanged. |

Any other key is an error.

Example of the common keys:

```php
'site_name' => [
    'type'       => 'text',
    'label'      => '  Site name ',
    'comment'    => ' Shown in the browser tab ',
    'class'      => ' wide ',
    'validation' => [' required-entry ', 'validate-url'],
    'required'   => true,
    'provider'   => ' My\Provider ',
]
```

Result:

```php
[
    'type'       => 'text',
    'label'      => 'Site name',
    'sort_order' => 0,
    'default'    => '',
    'placeholder' => '',
    'comment'    => 'Shown in the browser tab',
    'class'      => 'wide',
    'validation' => ['required-entry', 'validate-url'],
    'required'   => true,
    'provider'   => 'My\Provider',
    'depends'    => null,
]
```

---

## `text` and `textarea`

A single-line and a multi-line field. The rules are the same.

| Key | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `default` | string | `''` | Default value. Trimmed. |
| `placeholder` | string | `''` | Hint inside the field. Trimmed. |

```php
['type' => 'text', 'default' => '  Hello  ']
// → ['type' => 'text', 'default' => 'Hello', 'placeholder' => '']
```

## `password`

A secret (password, API key). The library neither encrypts nor masks anything; that is the renderer's job.

| Key | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `default` | string | `''` | Trimmed, so that accidental spaces from copying do not break the key. |

A number or any other type in `default` is an error: `Field 'default' for field 'f' must be a strict string.`

## `boolean`

A yes/no switch.

| Key | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `default` | bool | `false` | Only a real `true` or `false`. |

```php
['type' => 'boolean']                      // → default: false
['type' => 'boolean', 'default' => 1]      // error: 1 is not a bool
['type' => 'boolean', 'default' => 'true'] // error: Field 'default' for field 'f' must be a strict boolean.
```

## `yes_no`

A "No / Yes" drop-down without a description of the options.

| Key | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `default` | `0` or `1` | `0` | The strings `'0'` and `'1'` also fit; they are cast to a number. |

The options are always `[0 => 'No', 1 => 'Yes']`. The `options` key is **forbidden** for this type (it used to be silently replaced).

```php
['type' => 'yes_no']                   // → options [0 => 'No', 1 => 'Yes'], default 0
['type' => 'yes_no', 'default' => '1'] // → default 1
['type' => 'yes_no', 'default' => 2]   // error: The default value '2' for field 'f' does not exist within the permitted options scope.
['type' => 'yes_no', 'options' => [1 => 'On']] // error: Unknown key 'options' in field 'f'.
```

## `number`

A numeric field.

| Key | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `min` | int or float | none | Lower bound. |
| `max` | int or float | none | Upper bound. |
| `default` | int or float | `0`, see below | Default value. |

Rules:

- Only `int` and `float`. The string `"10"` is an error.
- `NAN` and `INF` are forbidden.
- `max` cannot be less than `min`.
- If `default` is given, it must lie within the range (bounds inclusive).
- If `default` is **not given**, `0` is taken. And if `0` is outside the range, the nearest bound is taken.

```php
['type' => 'number']                                  // default 0
['type' => 'number', 'min' => 5]                      // default 5, not 0
['type' => 'number', 'min' => -10, 'max' => -1]       // default -1
['type' => 'number', 'min' => 0.5, 'max' => 2.5, 'default' => 1] // default 1
['type' => 'number', 'min' => 100, 'max' => 10]       // error: Field 'max' cannot be less than 'min'...
['type' => 'number', 'min' => '1']                    // error: Field 'min' for number field 'f' must be a strict integer or float.
```

## `datetime`

A date, a time, or a date with time. The format is fixed, and the real calendar is checked.

| Key | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `view_mode` | string | `'datetime'` | `datetime`, `date` or `time`. |
| `default` | string | `''` | Value in the format of the chosen mode. Trimmed. An empty string is allowed. |

| `view_mode` | Format | Example |
| :--- | :--- | :--- |
| `datetime` | `Y-m-d H:i:s` | `2026-11-25 18:30:00` |
| `date` | `Y-m-d` | `2026-06-01` |
| `time` | `H:i:s` | `14:30:00` |

```php
['type' => 'datetime']                                                    // view_mode 'datetime', default ''
['type' => 'datetime', 'view_mode' => 'time', 'default' => ' 14:30:00 ']  // default '14:30:00'
['type' => 'datetime', 'view_mode' => 'date', 'default' => '2026-02-31']  // error: no such date
['type' => 'datetime', 'view_mode' => 'year']                             // error: Unsupported view_mode 'year'. Supported: datetime, date, time.
```

## `dynamic_rows`

A table to which the administrator adds rows (for example, shipping rates).

| Key | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `columns` | array | **required** | `column id => title`. Key and title are trimmed. |
| `default` | array of array | `[]` | Default rows. |

Rules for `columns`:

- At least one column is needed.
- A column id is a string that is not empty after trimming. Two ids that collide after trimming (`'a'` and `' a'`) are an error.
- An id made only of digits does not fit (PHP would turn it into a number).

Rules for rows in `default`:

- Every row is an array.
- A row must not use a column that is not in `columns`.
- Cell values: string or number. A number becomes a string. An array or `bool` is an error.
- If a cell is missing, `''` is filled in, so all rows have the same shape.

```php
[
    'type'    => 'dynamic_rows',
    'columns' => ['weight' => ' Weight ', 'cost' => 'Cost'],
    'default' => [
        ['weight' => 5, 'cost' => ' 12.5 '],
        ['weight' => '10'],                     // cost is missing
    ],
]
// → columns: ['weight' => 'Weight', 'cost' => 'Cost']
//   default: [['weight' => '5', 'cost' => '12.5'], ['weight' => '10', 'cost' => '']]
```

```php
['type' => 'dynamic_rows', 'columns' => []]
// error: Field 'columns' for dynamic rows field 'f' must declare at least one column.
['type' => 'dynamic_rows', 'columns' => ['w' => 'W'], 'default' => [['w' => '1', 'zzz' => '2']]]
// error: Row at index '0' inside dynamic rows field 'f' contains unknown column 'zzz'.
```

## `file` and `image`

A description of the rules for uploading a file or an image. `image` is no different from `file`; it is a separate name for the renderer.

| Key | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `upload_dir` | string | `''` | Destination folder. Trimmed. The `..` segment and null bytes are not allowed. |
| `extensions` | string | `''` | Comma-separated list of extensions, **without dots**. All whitespace is removed. |

```php
['type' => 'file', 'upload_dir' => ' media/docs ', 'extensions' => ' pdf, docx ']
// → upload_dir 'media/docs', extensions 'pdf,docx'

['type' => 'image', 'upload_dir' => 'media/v1..2/files']   // allowed: two dots inside a name are not path traversal
['type' => 'file', 'upload_dir' => '../etc']               // error: must not contain '..' segments or null bytes.
['type' => 'file', 'extensions' => '.jpg']                 // error: must be a comma-separated list of extensions without dots (e.g. 'jpg,png').
['type' => 'file', 'extensions' => 'jpg,,png']             // error: empty list item
```

The library does **not** check whether the folder exists or is allowed. Your code decides that at upload time.

## `select` and `radio`

Choosing one option. `radio` follows the same rules.

| Key | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `options` | array | **required** | `key => label`. Not empty. Labels are trimmed. |
| `default` | string or int | first key of `options` | One of the `options` keys. |

Rules:

- Option keys: strings or integers. Labels: strings.
- `default` must be among the keys of `options`.
- Numeric strings are cast to a number the same way PHP does it with array keys, so `'2'` becomes `2` if the option is written as `2 => 'Two'`.

```php
['type' => 'select', 'options' => ['b' => ' B ', 'a' => 'A']]
// → options ['b' => 'B', 'a' => 'A'], default 'b' (the first one)

['type' => 'select', 'options' => [1 => 'One', 2 => 'Two'], 'default' => '2']
// → default 2 (a number, like the key)

['type' => 'select', 'options' => []]
// error: Field 'f' of type 'select' must contain a non-empty array of 'options'.
['type' => 'select', 'options' => ['a' => 'A'], 'default' => 'b']
// error: The default value 'b' for field 'f' does not exist within the permitted options scope.
```

Messages carry the real field type (`radio` for radio).

## `multiselect` and `checkbox`

Choosing several options. `checkbox` follows the same rules.

| Key | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `options` | array | **required** | Same as for `select`. |
| `default` | array | `[]` | List of selected keys. |

Rules for `default`:

- It is an array of keys from `options`.
- Repeats are forbidden. `'1'` and `1` count as the same option.
- The result is always a **list** (`[...]`), keys are cast to the type of the `options` keys, and the order is kept.

```php
['type' => 'multiselect', 'options' => ['de' => 'DE', 'it' => 'IT', 'fr' => 'FR'], 'default' => ['it', 'de']]
// → default ['it', 'de']

['type' => 'multiselect', 'options' => [1 => 'One', 2 => 'Two'], 'default' => ['2', 1]]
// → default [2, 1]

['type' => 'multiselect', 'options' => ['DE' => 'Germany'], 'default' => ['DE', 'DE']]
// error: The default value 'DE' for field 'f' is listed more than once.
['type' => 'multiselect', 'options' => ['a' => 'A'], 'default' => 'a']
// error: Field 'default' for multiselect field 'f' must be a strict array of keys.
```

## `infoblock`

An information block. It is not an input field: it needs no value and only shows text.

| Key | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `text` | string | **required** | Block text. Trimmed. |
| `format` | string | `'text'` | `text` or `html`. Edge whitespace is trimmed. |

```php
['type' => 'infoblock', 'text' => '  Careful!  ']
// → text 'Careful!', format 'text'

['type' => 'infoblock', 'text' => '<b>Careful</b>', 'format' => 'html']
```

What this means for the renderer:

- `text`: output as plain text, **always escape** (`htmlspecialchars`).
- `html`: markup is allowed, the safety of the content is on your side.

The library neither cleans nor escapes the text.

Other fields **cannot** depend on an `infoblock`: it has no value.

```php
['type' => 'infoblock']                                        // error: Field 'text' is mandatory for infoblock type in 'f'.
['type' => 'infoblock', 'text' => 'x', 'format' => 'markdown'] // error: Unsupported format 'markdown' in infoblock 'f'. Supported: text, html.
```

---

## Cheat sheet: which keys belong to which type

In addition to the common keys (`type`, `label`, `sort_order`, `depends`, `comment`, `class`, `validation`, `required`, `provider`):

| Type | Additional keys |
| :--- | :--- |
| `text`, `textarea` | `default`, `placeholder` |
| `password` | `default` |
| `boolean` | `default` |
| `yes_no` | `default` |
| `number` | `default`, `min`, `max` |
| `datetime` | `view_mode`, `default` |
| `dynamic_rows` | `columns`, `default` |
| `file`, `image` | `upload_dir`, `extensions` |
| `select`, `radio` | `options`, `default` |
| `multiselect`, `checkbox` | `options`, `default` |
| `infoblock` | `text`, `format` |

A key from another row of the table is an `Unknown key` error. This catches typos and accidentally copied parameters.