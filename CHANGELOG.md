# Changelog

All notable changes to this package are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the package adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

The package is pre-1.0; everything below currently ships on `main`.

### Added

- Lang-file sync: import PHP (including nested directories), JSON and vendor files; export back to the
  same paths, preserving nesting, sorting, plurals and Unicode.
- `Translations` facade and `TranslationManager`: `get` / `set` / `has` / `forget` / `all`, plus
  locale management.
- AI translation via the `laravel/ai` SDK behind a swappable `Translator` contract, with prompt
  fencing, a provider allowlist, glossary/context-aware prompts, cost estimation and usage logging.
- Eight pluggable quality checks with auto-fix, run on save and on demand.
- Revision history with single and bulk rollback; analytics (per-locale and per-bundle coverage,
  velocity, stale detection, leaderboards); glossary; review workflow; activity logging.
- Source scanners for key usage (context) and hardcoded strings.
- Nine artisan commands, event-driven listeners, and queued jobs (`--queue` flags and
  `scanning.scan_after_import`).

### Security

- Bulk imports run in a transaction and suppress per-row events; a failed `--fresh` import rolls back
  instead of emptying the catalog.
- AI prompts fence untrusted context, and a requested AI provider is validated against an allowlist.
  See [`SECURITY.md`](SECURITY.md) for the full trust model.
