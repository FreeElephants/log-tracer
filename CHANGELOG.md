# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.0.3] - 2025-11-19

### Added
- `populateWithValues()` method

### Fixed
- `$update` arg in `getParentId()` at interface level

## [0.0.2] - 2025-11-18

### Added
- TraceRequestMiddleware
- TraceResponseMiddleware

### Changed
- Remove composer/semver dep

### Fixed
- Init SimpleTraceContext on trace message if not

## [0.0.1] - 2025-11-17

### Added
- Monolog v2 + v3 Processors
- Sentry\TraceContext
- SimpleTraceContext
- Common pkg classes

[Unreleased]: https://github.com/FreeElephants/log-tracer/compare/0.0.3...HEAD
[0.0.3]: https://github.com/FreeElephants/log-tracer/releases/tag/0.0.3
[0.0.2]: https://github.com/FreeElephants/log-tracer/releases/tag/0.0.2
[0.0.1]: https://github.com/FreeElephants/log-tracer/releases/tag/0.0.1
