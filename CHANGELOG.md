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

## [1.2.0] - Upcoming

### Changed

- Optimized creation of related resources in `ResourceModel`.

## [1.1.0] - 2024.11.11

### Added

- Added `Filterable` trait.

### Changed

- Updated `ResourceModel` field validation to occur before any field mutations.
- Removed unnecessary import of `OrmService` in `OrmServiceFilters` class constructor.

### Fixed

- Fixed reference to `Bayfront\BonesService\Orm\Exceptions\InvalidConfigurationException` in the `model-resource` template.
- Fixed bug when checking that search fields are readable in `ResourceModel`.
- Fixed bug in query when performing a search with `ResourceModel` `list` method.

## [1.0.0] - 2024.11.10

### Added

- Initial release.