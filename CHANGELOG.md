# Changelog

## [Unreleased]

### Fixed
- Identifier validation accepted a trailing newline (`"abc\n"`); the pattern is now anchored strictly.
- `number`: the implicit default (`0`) could violate the field's own `min`/`max`; it now falls back to the nearest allowed bound.
- `number`: `NAN` and `INF` are rejected for `min`, `max` and `default`.
- `dynamic_rows`: non-scalar cell values no longer produce an `"Array"` string and a PHP warning; unknown columns in default rows are now rejected as documented; empty, blank and duplicate column ids are rejected.
- `datetime`: an unsupported or non-string `view_mode` was silently replaced by `datetime`; it is now rejected as documented.
- `file` / `image`: `extensions` are now stripped of whitespace as documented and validated as a comma-separated list; `upload_dir` rejects `..` segments and null bytes.
- `Fields`: invalid custom validators passed to the constructor were silently ignored; `registerType()` accepted arbitrary type names. Both now throw `InvalidArgumentException`.
- `phpunit.xml` pointed to a non-PHPUnit schema URL.

### Added
- Detection of self-dependencies and circular `depends` chains between fields.
- `tabs` and `sections` always return `class` and `icon` (empty string by default), matching the documented output.
- Regression tests for all fixes above.
- CI matrix for PHP 8.3 and 8.4.

### Changed
- Documentation (`README.md`, `fields.md`) aligned with the actual behavior.
- Docblocks shortened.

## [1.0.1] - 2026-06-19

### Added
- Added optional `provider` property support across configuration field definitions.

### Fixed
- Prevented invalid configuration identifiers containing whitespace.
- Improved schema validation consistency for tabs, sections, groups and fields.
- Improved developer-facing validation error reporting.

## [1.0.0] - 2026-06-08

Initial stable release.

### Added
- SystemConfig schema
- GroupedConfig schema
- Configuration validation
- Dependency validation
- Sorting support
- Extensible validator architecture
