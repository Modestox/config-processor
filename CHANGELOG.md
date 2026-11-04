# Changelog

**English** | [Русский](CHANGELOG.ru.md)

## [2.0.0] - 2026-11-04

A stricter version than the previous ones: a config that passed in 1.x may stop passing. A detailed migration table is in the [README](README.md#upgrading-from-1x-to-20).

### Breaking changes
- Unknown keys are now an error (they used to be silently dropped). The error contains a `Did you mean ...?` hint and the list of allowed keys. Custom parameters must be named with the `x_` prefix; such keys go into the result without validation.
- `FieldValidatorInterface` gained the `allowedKeys()` method. Custom validators that do not extend `AbstractFieldValidator` must implement it. For subclasses, `specificKeys()` is enough.
- `yes_no`: the `options` key is forbidden (it used to be silently replaced by a fixed `No/Yes`).
- `multiselect` and `checkbox`: repeats in `default` are now an error.
- `select`, `radio`, `multiselect`, `checkbox`: `default` is cast to the type of the key in `options` (`'2'` becomes `2` if the option is written as `2 => ...`).
- `tabs` and `sections` always contain `class` and `icon` (default `''`).
- `depends` is checked more deeply: the value must exist among the parent's `options` (`select`, `radio`, `yes_no`), be a `bool` for `boolean` and a number for `number`; an `infoblock` cannot be a parent.
- New value checks: `dynamic_rows` (at least one column, no empty or duplicate ids, no extra columns in rows, cells only strings or numbers), `datetime` (a wrong `view_mode` instead of a silent replacement), `file` and `image` (`extensions` without dots and empty items, `upload_dir` without `..`), `number` (no `NAN` and `INF`).

### Added
- Error path: the message starts with `sections.api.groups.auth.fields.timeout:`, and the exception gained `getPath()`, `getReason()` and `prependPath()`.
- Detection of self-dependency and cycles in `depends`.
- `SystemConfig::withFieldTypes()` and `GroupedConfig::withFieldTypes()` for registering custom field types.
- A shared `AbstractNodeValidator` base class for Tabs, Sections, Groups and Fields; helper classes `Identifier` and `Keys`.
- More precise messages: "Defined tabs: ..." / "No tabs are defined." for a section with an unknown tab; the real field type in the messages of `select`, `radio`, `multiselect`, `checkbox`.
- Registering a field type with an invalid name or the wrong object throws `\InvalidArgumentException`.
- Regression tests for all the fixes below (125 tests).
- CI checks PHP 8.3 and 8.4; the Composer cache directory is determined with `composer config cache-files-dir`.

### Fixed
- An identifier with a trailing newline (`"abc\n"`) passed validation: the regular expression was not anchored to the end of the string.
- `number`: an unspecified `default` could end up outside `min`/`max` (for example `0` with `min => 5`). The nearest bound is taken now.
- `dynamic_rows`: an array in a cell produced the string `"Array"` and a PHP warning.
- `phpunit.xml` referenced the schema of another project; fixed.

### Documentation
- `README.md` and `fields.md` rewritten; examples of the output were verified by running the code.
- Translations: Russian (`*.ru.md`) and German (`README.de.md`, `fields.de.md`). The changelog is in English and Russian.
- Docblocks shortened.

## [1.0.1] - 2026-06-19

### Added
- Optional `provider` property of fields.

### Fixed
- Identifiers with spaces are now rejected.
- Uniform validation of tabs, sections, groups and fields.
- Clearer error messages.

## [1.0.0] - 2026-06-08

First stable release.

### Added
- `SystemConfig` and `GroupedConfig` schemas.
- Configuration validation and dependencies between fields.
- Sorting by `sort_order`.
- Extensible validator architecture.