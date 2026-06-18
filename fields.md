# Detailed Field Specifications & Properties

Every configuration field in the system, regardless of its specific type (`type`), supports and validates a set of **Common Properties** at the base level:
* `type` *(string, required)* — The unique type identifier of the field.
* `label` *(string, optional)* — The display name of the field. If omitted or an empty string is provided, it defaults to the field's array identifier key.
* `sort_order` *(int, optional)* — Sequential index for sorting within the group context. Defaults to `0`.
* `comment` *(string, optional)* — Helpful tooltip description rendered below the input element. Automatically stripped of surrounding whitespaces. Defaults to `""`.
* `class` *(string, optional)* — Additional CSS class for input UI customization. Automatically trimmed. Defaults to `""`.
* `validation` *(array, optional)* — An array of validation rule strings. Each rule is automatically stripped of whitespaces. Defaults to `[]`.
* `required` *(bool, optional)* — Defines whether filling the field is mandatory. Defaults to `false`.
* `depends` *(array, optional)* — Conditional visibility constraints. A field can depend on one or more parent fields within the same group. Passed as an associative array mapping parent field keys to expected scalar values (e.g., `['enable_api' => 1, 'api_mode' => 'live']`). Defaults to `null`.
* `provider` *(string, optional)* — FQCN (Fully Qualified Class Name) of the dynamic data supplier. Used to fetch dynamic options matrices at runtime. Defaults to `""`.

---

### 1. `text` (Single-line Text Input)
A standard input field for raw textual data.
* **Type-Specific Properties:**
  * `default` *(string, optional)* — Predefined fallback value. Automatically sanitized via `trim()`. Defaults to `""`.
  * `placeholder` *(string, optional)* — Inline contextual prompt text. Automatically stripped of surrounding whitespaces. Defaults to `""`.

### 2. `textarea` (Multi-line Text Input)
Used for large blocks of text input (descriptions, scripts, custom styles).
* **Type-Specific Properties:**
  * `default` *(string, optional)* — Predefined fallback value. Automatically trimmed. Defaults to `""`.
  * `placeholder` *(string, optional)* — Inline contextual prompt text. Automatically trimmed. Defaults to `""`.

### 3. `password` (Sensitive Password Input)
Designed for configuring sensitive credentials (passwords, private API keys, tokens). Expects a strict string.
* **Type-Specific Properties:**
  * `default` *(string, optional)* — Predefined secret value. Automatically stripped of accidental surrounding whitespaces to prevent authentication failures. Defaults to `""`.

### 4. `boolean` (Logical Toggle Switch)
A single toggle or checkbox for enabling/disabling a specific application feature.
* **Type-Specific Properties:**
  * `default` *(bool, optional)* — Logical fallback value. Requires a strict `true` or `false` type. If omitted from the configuration structure, the processor automatically injects `false`.

### 5. `yes_no` (Binary Dropdown Selection)
A specialized select element for fast declaration of boolean options without manual options array mapping.
* **Type-Specific Properties:**
  * `default` *(int, optional)* — Integer fallback value. Accepts strictly `0` or `1`. If omitted, the processor enforces a fallback to `0`.
* **Automated Behavior:**
  * The processor completely overrides any user-supplied `options` parameter and automatically hardcodes the binary options matrix: `[0 => 'No', 1 => 'Yes']`.

### 6. `number` (Numeric Input Field)
An input component for integers or floating-point values (limits, cache timeouts, primary IDs, prices).
* **Type-Specific Properties:**
  * `default` *(int|float, optional)* — Numeric fallback value. Defaults to `0` if not set.
  * `min` *(int|float, optional)* — Lower bounds boundary condition.
  * `max` *(int|float, optional)* — Upper bounds boundary condition.
* **Validation Rules:**
  * Passing string types (e.g., `"10"`) to `min`, `max`, or `default` will trigger a validation exception (`InvalidConfigException`).
  * If both `min` and `max` are defined, the processor strictly ensures that `max` is not less than `min`.
  * The `default` value must fall within the inclusive range defined by `min` and `max`, otherwise validation fails.

### 7. `datetime` (Temporal Timestamp Input)
A picker component for temporal dates, times, or unified timestamps built on strict ISO/24h standards.
* **Type-Specific Properties:**
  * `view_mode` *(string, optional)* — Defines the granularity and display format. Accepts three strict values:
    * `'datetime'` — Date combined with time (expects format `Y-m-d H:i:s`, e.g., `2026-11-25 18:30:00`). Enforced as default if omitted.
    * `'date'` — Date scope only (expects format `Y-m-d`, e.g., `2026-06-01`).
    * `'time'` — Time scope only (expects format `H:i:s`, e.g., `14:00:00`).
  * `default` *(string, optional)* — Default timestamp string.
* **Validation Rules:**
  * The `default` value is automatically trimmed. If provided, it must match the regular expression and logical format criteria of the chosen `view_mode` exactly.
  * Calendar dates are validated for real-world correctness (e.g., February 31st or 25:00:00 will be rejected).
  * Any structural discrepancy or unsupported `view_mode` will throw an `InvalidConfigException`.

### 8. `dynamic_rows` (Tabular Matrix Grid)
A complex matrix constructor permitting administrators to dynamically add rows of structured datasets (shipping rate grids, code mappings).
* **Type-Specific Properties:**
  * `columns` *(array, required)* — Associative array declaring the schema header definitions. Key is the string column ID; value is the UI header string (e.g., `['weight' => 'Max Weight', 'cost' => 'Price']`). Both keys and labels are automatically trimmed.
  * `default` *(array, optional)* — Multi-dimensional array representing predefined table rows.
* **Validation Rules:**
  * Omitting the `columns` block or passing an invalid type triggers a critical exception.
  * Every nested item within `default` must be an associative array. If an inner row declares a column key missing from `columns`, the processor aborts processing.
  * If a row within `default` misses a key defined in `columns`, the processor automatically backfills that column with an empty string `""` to ensure data structure predictability. All cell values are deeply trimmed.

### 9. `file` & `image` (Declarative Asset Upload Rules)
Configuration objects preparing destination rules for files and media objects handled by the core application runtime. `image` inherits all properties directly from `file`.
* **Type-Specific Properties:**
  * `upload_dir` *(string, optional)* — Target path destination mapping. Automatically trimmed. Defaults to `""`.
  * `extensions` *(string, optional)* — Comma-separated listing of permitted file extensions (e.g., `'jpg,png,webp'`). Automatically stripped of spaces. Defaults to `""`.
* **Architectural Blueprint:**
  * These definitions are purely declarative. They do not enforce strict systemic path assertions or default extensions, returning empty strings if omitted. This allows the calling application to apply global or context-aware file upload logic later.

### 10. `select` & `radio` (Single Option Pickers)
User interface controls enabling the choice of exactly one parameter out of a fixed selection matrix. Validation logic for `radio` is identical to `select`.
* **Type-Specific Properties:**
  * `options` *(array, required)* — Associative array containing the permitted choice set scope. Option keys must be strict strings or integers (`int`). Option display names must be strict strings. Option labels are automatically trimmed.
  * `default` *(string|int, optional)* — Selected item key on layout load.
* **Validation Rules:**
  * Supplying an empty options array or omitting the property entirely is forbidden.
  * The `default` key is strictly validated against the registered keys of `options`. If no `default` is passed, the processor automatically assigns the **first key** found within the `options` matrix.

### 11. `multiselect` & `checkbox` (Multiple Options Pickers)
User interface controls allowing a concurrent selection of several option keys. Validation logic for `checkbox` is completely inherited from `multiselect`.
* **Type-Specific Properties:**
  * `options` *(array, required)* — Associative array containing the choice scope. Key-value formatting assertions match the single option select criteria perfectly.
  * `default` *(array, optional)* — Strict array listing pre-selected options keys (e.g., `['png', 'webp']`).
* **Validation Rules:**
  * The `options` parameter is strict and cannot be empty.
  * The `default` property must be a strict array type. Each element within the array is validated to ensure it exists among the keys declared in `options`. If omitted, it returns an empty array `[]`.

### 12. `infoblock` (Non-Data Placeholder)
A specialized component used for rendering instructions, warnings, or inline notice blocks within configuration forms. It completely ignores data inputs.
* **Type-Specific Properties:**
  * `text` *(string, required)* — The message content. Surrounding whitespaces are automatically trimmed.
  * `format` *(string, optional)* — Defines how the content should be interpreted by the consuming application. Accepts two options:
    * `'text'` — Plain text format. The rendering frontend must escape the content (e.g., via `htmlspecialchars()`) to prevent XSS vulnerability risks. **Enforced as default if omitted.**
    * `'html'` — Raw markup rendering allowed. Developers are responsible for ensuring safe markup implementation when embedding anchor tags (`<a>`), line breaks, or text emphasis.
```