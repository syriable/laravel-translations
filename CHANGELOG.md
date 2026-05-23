# Changelog

All notable changes to `syriable/laravel-translations` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Support for Laravel 13 (alongside the existing Laravel 11 and 12 support).
- Configurable per-locale plural form counts via
  `config('translations.validation.plural.counts')`.

### Changed

- `PluralFormRule` is now language-aware: it validates a translation against the
  number of plural forms its own language uses (English 2, Polish 3, Arabic 6, …)
  instead of comparing the source and target segment counts.

### Fixed

- Plural validation no longer reports false positives for languages whose plural
  form count differs from the source language.
- `PhpArrayFormat` no longer discards an entire PHP language file when a single
  value is a non-constant expression; valid entries are preserved and only the
  unevaluable ones are skipped.

## [0.1.0-beta] - 2026-05-23

### Added

- Initial public beta of the package.
- Translation extraction engine with AST-based PHP scanning and Blade scanning.
- Pluggable scanner architecture via the `Scanner` contract.
- Storage abstraction with a file driver supporting PHP group, JSON and vendor namespaces.
- Pluggable file formats via the `FileFormat` contract (PHP array + JSON).
- Translation catalog domain model (immutable DTOs).
- Synchronization service to reconcile extracted keys with the catalog (dry-run aware).
- Health analysis: missing keys, unused keys and locale completeness.
- Validation pipeline with placeholder, plural and HTML-tag consistency rules.
- Artisan commands: `translations:extract`, `translations:sync`, `translations:import`,
  `translations:export`, `translations:validate`, `translations:health`, `translations:locales`.

[Unreleased]: https://github.com/syriable/laravel-translations/compare/v0.1.0-beta...HEAD
[0.1.0-beta]: https://github.com/syriable/laravel-translations/releases/tag/v0.1.0-beta
