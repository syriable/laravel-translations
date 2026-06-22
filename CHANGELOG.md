# Changelog

All notable changes to `syriable/laravel-translations` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the package
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

The package is pre-1.0; everything below currently ships on `main` and has not yet been tagged.

### Added

- **Core model and API.** A normalized `Locale → Bundle → Phrase → Message` schema (prefixed,
  optionally on a dedicated connection) behind a single `Translations` facade: `get`, `set`, `has`,
  `forget`, `all`, plus `locales()` / `addLocale()`.
- **Similar-key lookup.** `Translations::similar($key)` surfaces phrases in the same bundle that
  share a leading key segment (e.g. `validation.accepted` → `accepted_if`, `accepted_unless`), with
  `segments`, `limit` and `include_self` options.
- **Language-file sync.** Import PHP (including nested directories), JSON and vendor namespace files;
  export back to the same paths, preserving nesting, key sorting, plurals and Unicode. Nested files
  use their slash path as the bundle name (Laravel group convention).
- **AI translation** via the `laravel/ai` SDK behind a swappable `Translator` contract, with
  glossary/context-aware prompts, multiple variants, cost estimation, and per-call usage logging.
- **Quality checks.** Eight pluggable checks (missing/unexpected placeholder, HTML, length ratio,
  whitespace, casing, URL/email, glossary) that run on save and on demand, with auto-fix for
  whitespace and casing.
- **Revision history** with single rollback and bulk rollback by author or date.
- **Glossary** of per-locale approved terminology feeding AI prompts and the glossary check.
- **Analytics.** Per-locale and per-bundle coverage, velocity, stale detection and contributor
  leaderboards, behind a cache.
- **Review workflow**, **activity logging**, and source scanners for key usage (context) and
  hardcoded strings.
- **Nine artisan commands**, event-driven listeners, and queued jobs (`--queue` flags and
  `scanning.scan_after_import`).
- `SECURITY.md`, `CONTRIBUTING.md` and this changelog.

### Changed

- Bundle coverage analytics (`Insights::bundleCoverage()`, `Bundle::withTranslationProgress()`,
  `translations:status --bundles`).
- `Insights::coverage()` now uses a single grouped aggregate instead of three count queries per locale.
- Batch AI translation eager-loads phrase usages and memoizes sibling keys per bundle; the glossary
  term set and the import exclusion list are memoized per run — all removing N+1 queries.

### Fixed

- Imports run inside a database transaction and suppress per-row model events, so bulk/`--fresh`
  imports no longer write a revision per string or run inline quality on every row — and a failed
  `--fresh` import rolls back instead of leaving the catalog empty.
- Revision-stamp context is bracketed with `Message::withStamp()` (try/finally), so an exception
  during a save can no longer leak the reason/author onto the next saved message.
- The analytics cache is invalidated automatically on writes and imports.
- The four queued jobs are now actually dispatched (via `--queue` flags and the new
  `scanning.scan_after_import` config key) rather than being dead code.

### Security

- AI prompts fence untrusted context (glossary, notes, usages, source text) and validate the
  requested `tone`, so injected text can't act as model instructions.
- Caller-supplied AI providers are validated against `ai.allowed_providers`.
- Documented the package's trust model in `SECURITY.md` (lang-file PHP execution on import,
  mass-assignable models, plaintext history, untrusted AI output, consumer-owned authorization).

[Unreleased]: https://github.com/syriable/laravel-translations/commits/main
