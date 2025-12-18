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

## [1.3.0] - Upcoming

### Added

- Added `binToUuid` and `uuidToBin` methods to `Castable` trait

## [1.2.1] - 2025.12.09

### Changed

- Updated dependencies
- Updated Symfony console depreciated `add` function to `addCommand`

## [1.2.0] - 2025.11.06

### Changed

- Updated `HasNullableJsonField` to not dot arrays
- Updated `jsonDecode` method of `Castable` trait to order keys alphabetically
- Updated `ResourceModel` to allow filtering by related fields
- Updated documentation

### Removed

- Removed `getNullableJsonField` method in `HasNullableJsonField` trait. 
  More than one field may exist in the same model.

### Fixed

- Fixed bugs in `ResourceModel`
  - Added table aliases to joined tables
  - Ignore checking uniqueness when value is `null`
  - ...and others

## [1.1.2] - 2025.06.18

### Fixed

- Fixed bug in `ResourceModel` by assigning aliases to foreign keys

## [1.1.1] - 2025.06.18

### Added

- Added key validation in `HasNullableJsonField` trait

## [1.1.0] - 2025.05.27

### Added

- Added `getNullableJsonField` method in `HasNullableJsonField` trait

### Changed

- Updated `updateNullableJsonField` method in `HasNullableJsonField` trait to properly query the correct meta field

## [1.0.2] - 2025.03.07

### Fixed

- Fixed bug merging meta in `HasNullableJsonField` trait

## [1.0.1] - 2025.02.07

### Fixed

- Fixed bug in `HasNullableJsonField` trait

## [1.0.0] - 2025.01.09

### Added

- Initial release