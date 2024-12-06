# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

- `[Unreleased]` for upcoming features.
- `Added` for new features.
- `Changed` for changes in existing functionality.
- `Deprecated` for soon-to-be removed features.
- `Removed` for now removed features.
- `Fixed` for any bug fixes.
- `Security` in case of vulnerabilities

## [1.0.0-beta.1] - Upcoming

### Added

- Added `$required_fields` property to `ResourceModel`
- Added `getRequiredFields`, `getAllowedFieldsWrite` and `getAllowedFieldsRead` methods to `ResourceModel`.
- Added ability to read only selected fields in `ResourceModel`

### Changed

- If no fields are defined in the `QueryParserInterface`, all readable fields will be returned.

## [1.0.0-beta] - 2024.11.29

### Added

- Initial beta release.