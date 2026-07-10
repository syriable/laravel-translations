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
  Each suggestion exposes a clean, copy/store-ready `base_value` (via `TranslationResult::best()`)
  alongside the model's proposed text (`proposed()`), so framing like `for example: "…"` never leaks
  into stored translations.
- **Quality checks.** Eight pluggable checks (missing/unexpected placeholder, HTML, length ratio,
  whitespace, casing, URL/email, glossary) that run on save and on demand, with auto-fix for
  whitespace and casing.
- **AI quality review** via the `laravel/ai` SDK behind a swappable `Reviewer` contract:
  `Translations::aiReview()->review($locale)` (and the `translations:ai-review` command) batch the
  locale's translated source/target pairs to the model to flag unnatural phrasing, gender issues,
  pluralization errors, context mismatches and cross-key inconsistencies. Returns a `ReviewResult`
  of `ReviewIssue`s, each graded on a dedicated `ReviewSeverity` (`Low`/`Medium`/`High`) priority
  scale and carrying a clean, copy-ready `baseSuggestion` when the reviewer proposes a corrected
  translation, with per-batch usage logging; untrusted text is fenced and hallucinated keys are dropped.
- **Revision history** with single rollback and bulk rollback by author or date.
- **Automatic actor resolution.** `Revision::member()`, `Message::translator()` and
  `Message::reviewer()` are `belongsTo` relations against the configured `member_model`, so the user
  behind a change is available without a manual lookup. Every write path (`set()`, AI translation,
  review approve/reject, quality auto-fix, rollback) now resolves `changed_by` / `translated_by` /
  `reviewed_by` from `Contracts\ResolvesActor` whenever an explicit `by` isn't given — including who
  *triggered* an AI translation, not the AI itself. Ships with an `Auth`-guard-backed default
  (`auth_guard` / `system_actor` config), swappable by rebinding the contract. Quality auto-fixes
  (`Inspector::fix()`) now go through the same stamping pipeline and record a revision under the new
  `RevisionReason::QualityFix`, instead of silently bypassing revision history.
- **Glossary** of per-locale approved terminology feeding AI prompts and the glossary check.
- **Analytics.** Per-locale and per-bundle coverage, velocity, stale detection and contributor
  leaderboards, behind a cache.
- **Review workflow**, **activity logging**, and source scanners for key usage (context) and
  hardcoded strings.
- **Nine artisan commands**, event-driven listeners, and queued jobs (`--queue` flags and
  `scanning.scan_after_import`).
- `SECURITY.md`, `CONTRIBUTING.md` and this changelog.

### Changed

- **Removed the `Member` model, `members` table and `member_locale` foreign key.** The package no
  longer owns an actor table. Add `Contracts\HasTranslationRole` to whichever model represents your
  translators (your own `App\Models\User` by default, configurable via `translations.member_model` /
  `TRANSLATIONS_MEMBER_MODEL`) to resolve a `MemberRole` for permission checks. `Locale::members()`,
  `Activity::member()` and `Comment::member()` now relate to the configured `member_model` instead of
  the removed `Member` class, and `member_locale.member_id` is a plain indexed string column (no FK).
  A `Policies\MessagePolicy` stub is included but not auto-registered.
- Bundle coverage analytics (`Insights::bundleCoverage()`, `Bundle::withTranslationProgress()`,
  `translations:status --bundles`).
- `Insights::coverage()` now uses a single grouped aggregate instead of three count queries per locale.
- Batch AI translation eager-loads phrase usages and memoizes sibling keys per bundle; the glossary
  term set and the import exclusion list are memoized per run — all removing N+1 queries.

### Fixed

- `Translations::set()` and AI translation (`MachineTranslation::apply()`) now no-op when the
  submitted value is identical to the message's current value, instead of unconditionally
  resetting `status` back to `Draft` and touching `translated_by` on every resave. Previously,
  resubmitting unchanged text — a duplicate form submit, a re-run AI suggestion that comes back
  the same — silently downgraded an already-approved translation and left revision history
  untouched (revisions were already deduplicated by value), an inconsistent pairing. Only a save
  that actually changes the value now stamps status/actor and creates a revision.
- `CasingCheck` no longer flags a false casing mismatch when the source or target's first letter
  belongs to a script without letter case (Arabic, Hebrew, CJK, ...). `mb_strtoupper()` is a no-op
  on such letters, so the previous logic always read them as "uppercase" and compared that against
  the Latin-script source, flagging spurious warnings whenever the source started lowercase.
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
