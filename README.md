# Laravel Translations

A clean, **backend-only** toolkit for managing your Laravel translations from code. It imports and
exports language files, machine-translates with AI, enforces quality, tracks revision history,
detects hardcoded strings, manages a glossary, and reports analytics — all through one small,
Spatie-style API. No UI, no frontend, no opinions about how you render anything.

```php
use Syriable\Translations\Facades\Translations;

Translations::import();                                  // lang files  -> database
Translations::set('auth.failed', 'Echec', 'fr');         // write a value
Translations::get('auth.failed', 'fr');                  // 'Echec'
Translations::translate('auth.failed', 'de');            // AI translation
Translations::export();                                   // database -> lang files
```

---

## 1. Package overview

This package unifies two previously separate products — a free translation manager and a paid "pro"
add-on — into a **single, backend-only package** with one coherent API. Everything the two packages
did on the server is here; nothing that touched the browser is.

**Unified feature set**

| Area | What you get |
| --- | --- |
| **Lang file sync** | Import PHP (including nested subdirectories), JSON and vendor namespace files into the DB; export them back to the same paths, preserving nesting, sorting, plurals and Unicode. Imports are atomic (wrapped in a transaction). |
| **Programmatic API** | `get` / `set` / `has` / `forget` / `all` over a normalized `locale → bundle → phrase → message` model. |
| **AI translation** | Single-key and whole-locale machine translation via the `laravel/ai` SDK, with glossary + code-context-aware prompts, cost estimation and per-call usage logging. |
| **Quality checks** | Eight pluggable checks (placeholders, HTML, length ratio, whitespace, casing, URLs/emails, glossary) that run on save and on demand, with auto-fix. |
| **Revision history** | Every value change is recorded; roll back a single message, or in bulk by author or date. |
| **Glossary** | Per-locale approved terminology that feeds both AI prompts and the glossary quality check. |
| **Hardcoded-string detection** | Scan Blade/JS/PHP for untranslated user-facing strings, with a false-positive filter and an ignore list. |
| **Context scanning** | Record where each key is used in your codebase to enrich AI prompts. |
| **Review workflow** | Optional approval gate; non-reviewer edits land in "pending review". |
| **Analytics** | Coverage (per locale and per bundle), velocity, stale detection and contributor leaderboards. |
| **Activity log** | Record arbitrary member actions for auditing. |

---

## 2. Core design concept — how two packages became one

The original "free" package owned the data model and the import/export engine. The "pro" package
bolted features on from the outside: it shipped its own tables, its own service provider, and hooked
into the free package through two events (`TranslationSaved`, `ImportCompleted`).

This package removes that seam. The realization is that **"pro" was never a separate domain — it was
just more behavior reacting to the same lifecycle.** So the rewrite keeps the event lifecycle as the
backbone and folds every pro feature into it as a first-class service:

```
                       ┌──────────── Translations facade (one entry point) ────────────┐
                       │                                                                │
   lang files ──import──►  Locale · Bundle · Phrase · Message  ──export──► lang files   │
                       │            │                                                   │
                       │       MessageSaved event                                       │
                       │       ├─ RecordRevision      (history)                         │
                       │       ├─ RunQualityChecks    (validation)                      │
                       │       └─ FlushInsightsCache  (analytics)                       │
                       │       ImportFinished event                                     │
                       │       └─ ScanUsageAfterImport(context)                         │
                       │                                                                │
                       │  on-demand services: AI · Glossary · Insights · Scanners       │
                       └────────────────────────────────────────────────────────────────┘
```

The same five core tables the free package used are still the spine; the nine pro tables become
plain support tables owned by the same package. One config file, one service provider, one facade.

**What was deliberately dropped:** all HTTP controllers, routes, form requests, Inertia/React views
and assets. They belong to whatever UI you choose to build on top. This package is the engine.

---

## 3. Folder structure (Spatie-style)

```
src/
├── TranslationsServiceProvider.php   # single entry point: config, migrations, commands, listeners
├── TranslationManager.php            # the unified API behind the facade
├── Facades/Translations.php
├── Models/                           # Eloquent models (prefixed tables, no business logic)
├── Enums/                            # MessageStatus, MemberRole, Severity, RevisionReason, ...
├── Contracts/                        # QualityCheck, Translator, SourceScanner
├── Support/                          # value objects: Issue, *Summary, *Request/Result, seeders
├── Files/                            # LangReader / LangWriter (php + json)
├── Importing/LangImporter.php        # disk -> database
├── Exporting/LangExporter.php        # database -> disk
├── Ai/                               # MachineTranslation, AiTranslator, FakeTranslator, prompts, cost
├── Quality/                          # Inspector + Checks/*
├── Glossary/Glossary.php
├── Revisions/RevisionRollback.php
├── Analytics/                        # Insights + BundleCoverage
├── Scanning/                         # Usage (context) + Loose (hardcoded) scanners
├── Events/  Listeners/  Jobs/
└── Commands/                         # 9 artisan commands
config/translations.php
database/migrations/                  # two migrations: core tables + support tables
tests/                                # Pest: Unit + Feature
```

No `Domain/Application/Infrastructure` layering. Each service is a flat class with one job, resolved
straight from the container.

---

## 4. Core classes

| Class | Responsibility |
| --- | --- |
| `TranslationManager` | The public API. Resolves dotted keys to phrases, reads/writes messages, and exposes every sub-service. Backs the `Translations` facade. |
| `LangImporter` | Walks the lang path, reads PHP/JSON/vendor files, creates `Locale`/`Bundle`/`Phrase`/`Message` rows, detects placeholders/HTML/plurals, seeds missing target messages, records an `ImportRecord`, fires `ImportFinished`. Runs in a transaction and suppresses per-row events. |
| `LangExporter` | Reads messages back out (optionally approved-only), inflates dotted keys to nested arrays and writes PHP/JSON files, fires `ExportFinished`. |
| `LangReader` / `LangWriter` | The only file-format code. Reader flattens with `Arr::dot`; writer inflates, sorts and pretty-prints. |
| `Message` (model) | Central record. Fires `MessageSaved` whenever a value changes, carrying the old value plus an optional "stamp" (reason/author/meta) used for revisions. `withStamp()` brackets a stamped save so the context can never leak. |
| `Inspector` | Runs the configured `QualityCheck` list against a message versus its source, persists `QualityIssue` rows, and applies auto-fixes. |
| `MachineTranslation` | Builds a context-rich `TranslationRequest` (glossary + usages + siblings + tone), delegates to a `Translator`, applies the best result, and logs `AiUsage`. |
| `AiTranslator` / `FakeTranslator` | The two `Translator` implementations. `AiTranslator` drives a `laravel/ai` structured-output agent; `FakeTranslator` is deterministic for tests and local use. |
| `Glossary` | CRUD for terms + per-locale definitions, and term matching used by AI prompts and the glossary check. |
| `RevisionRollback` | Restore a message to a revision, or bulk-undo changes by author/date. |
| `Insights` / `BundleCoverage` | Cached coverage (per locale and per bundle), velocity, stale and leaderboard analytics. The cache is flushed automatically on writes and imports. |
| `UsageScanner` / `LooseStringScanner` | Source-code scanners for key usage (context) and hardcoded strings. |

**How they interact:** the facade delegates to `TranslationManager`, which calls importer/exporter
directly and resolves the rest from the container on demand. Writes go through the `Message` model,
whose `MessageSaved` event drives revisions and quality checks via listeners — so history and
validation happen no matter *who* changed the value (manual, AI, or rollback). Bulk imports run
inside a transaction with model events suppressed, so they neither bloat history with a revision per
imported string nor run inline quality on every row — validate imported catalogs with
`translations:validate` afterwards.

---

## 5. Public API (developer experience)

### Facade

```php
use Syriable\Translations\Facades\Translations;

// Values
Translations::set('cart.checkout', 'Checkout', 'en');
Translations::set('cart.checkout', 'Pagar', 'es', ['by' => 'maria', 'reason' => 'manual']);
Translations::get('cart.checkout', 'es');          // 'Pagar'
Translations::has('cart.checkout', 'es');          // true
Translations::forget('cart.checkout', 'es');       // clear one locale
Translations::all('es');                            // ['cart.checkout' => ..., ...]

// Locales
Translations::addLocale('de');                      // seeds open messages for every phrase
Translations::locales();

// Sync
Translations::import(['fresh' => true]);
Translations::export(['locale' => 'es']);

// AI
Translations::translate('cart.checkout', 'de');     // one key
Translations::ai()->translateOpen($germanLocale);   // every untranslated message

// Quality, glossary, revisions, analytics, review
Translations::quality()->scan();
Translations::glossary()->define('invoice');
Translations::revisions()->byMember('maria');
Translations::insights()->dashboard();
Translations::insights()->coverage();         // progress per locale
Translations::insights()->bundleCoverage();   // progress per bundle (lang file)
Translations::review()->approve($message, 'lead-reviewer');
```

### Service / container style

Every sub-service is bound in the container, so you can inject it instead of using the facade:

```php
use Syriable\Translations\TranslationManager;
use Syriable\Translations\Quality\Inspector;

app(TranslationManager::class)->get('cart.checkout', 'es');
app(Inspector::class)->scan(localeId: 3);
```

### Swapping the AI engine

`Translator` is a one-method contract, so tests and custom providers need no mocking:

```php
use Syriable\Translations\Contracts\Translator;
use Syriable\Translations\Ai\FakeTranslator;

$this->app->instance(Translator::class, new FakeTranslator(
    fn ($request) => strtoupper($request->text),
));
```

---

## 6. Internal flow

```
set()      → DB transaction { resolve/create phrase → withStamp(reason, by) { Message::save() } }
                                                            └─ MessageSaved
                                                                 ├─ RecordRevision     → revisions row
                                                                 ├─ RunQualityChecks    → quality_issues rows
                                                                 └─ FlushInsightsCache  → drop analytics cache

import()   → DB transaction + events suppressed { read php/json/vendor → upsert phrase + message
           → seed missing target } → ImportRecord → ImportFinished → flush cache → (opt. context scan)

export()   → load messages (approved-only?) → inflate dotted keys → write php/json → ExportFinished

translate()→ build source text → gather glossary + usages + siblings → Translator::translate()
           → apply best variant (ai_generated=true) → AiUsage log → MessageSaved (revision + checks)

validate() → for each translated message → run checks vs source → persist issues → auto-fix fixable
```

---

## 7. Configuration

A single `config/translations.php`. Highlights and the "why":

| Key | Why it exists |
| --- | --- |
| `source_locale` | The language you author in; every other locale is a translation target. |
| `lang_path` | Where import reads and export writes. |
| `database.prefix` / `database.connection` | Every table is prefixed (default `tx_`) and can live on a dedicated connection, so the package never clashes with your schema. |
| `import.detect_*`, `import.scan_vendor`, `import.exclude_files` | Control metadata extraction and which files participate. |
| `export.sort_keys`, `export.exclude_empty`, `export.approved_only` | Shape the files you ship — e.g. only export reviewer-approved strings. |
| `review.enabled` | Turn the approval gate on/off. |
| `revisions.enabled`, `revisions.retention_days` | History tracking and pruning. |
| `ai.*` | Provider, model, variant count, batch size and per-model `cost_rates` (USD per 1M chars) used for estimates. |
| `quality.checks`, `quality.run_on_save`, `quality.length_ratio.overrides` | The pluggable check list and per-language length tuning. |
| `scanning.paths`, `scanning.extensions`, `scanning.scan_after_import`, `scanning.loose.*` | Where and how the source scanners look, and whether import queues a usage scan. |
| `analytics.*` | Cache TTL, stale threshold and leaderboard size (the dashboard cache is also flushed on every write/import). |
| `queue.*` | Connection/queue for the background jobs (`--queue` flags and `scanning.scan_after_import`). |

Disabling a feature is configuration, not code: remove a class from `quality.checks` to drop a check,
set `ai.enabled=false` to refuse AI calls, set `review.enabled=false` to auto-approve.

---

## 8. Testing strategy

Run with `composer test` (Pest 4 + Orchestra Testbench, in-memory SQLite).

- **Unit** — pure logic with no database: `PlaceholderScanner` (placeholder/HTML/plural/URL extraction)
  and `LangWriter`/`LangReader` round-trips (nesting, sorting, Unicode JSON).
- **Feature** — full Laravel integration:
  - import/export across PHP, JSON, vendor and nested files, plus the `--no-overwrite` path;
  - import hardening: atomic rollback on failure, and bulk imports skipping per-row revisions/quality;
  - the `get`/`set`/`forget`/`addLocale` API including on-demand phrase creation and locale seeding;
  - revisions: capture on change, single rollback, bulk rollback by author, stamp isolation, and the disabled-config path;
  - quality: missing-placeholder errors, HTML mismatches, auto-fix, and "source isn't checked against itself";
  - AI: applying a translation through a `FakeTranslator`, glossary/context forwarding, and whole-locale translation with usage logging;
  - scanning: recording key usages and detecting hardcoded strings while skipping translated ones;
  - bundle coverage: zero phrases, zero targets, partial and full per-bundle progress;
  - job wiring: `--queue` flags and scan-after-import dispatch onto the configured queue;
  - glossary + review workflow status transitions.
- **Edge cases covered**: empty target files, keys without a dot (JSON bundle), nested dotted keys,
  nested lang directories and filename collisions, RTL/Unicode values, the source locale never
  validating against itself, and revisions short-circuiting when the value didn't actually change.

The `Translator` contract means AI paths are tested deterministically with no HTTP and no mocking.

---

## 9. Migration strategy

This is a clean-room package with its own namespace (`Syriable\Translations`), table prefix (`tx_`)
and class names, so it installs **alongside** an existing setup without collisions. Recommended path:

1. **Install & publish**

   ```bash
   composer require syriable/laravel-translations
   php artisan vendor:publish --tag=translations-config
   php artisan migrate
   ```

2. **Re-seed from your lang files (recommended).** Both original packages were ultimately a cache of
   your lang files, so the cleanest migration is to re-import the source of truth:

   ```bash
   php artisan translations:import
   ```

   This rebuilds locales, bundles, phrases and messages, re-deriving placeholders/HTML/plural flags.

**Coming from the free package (`outhebox/laravel-translations`)**
   - Concept map: `Language → Locale`, `Group → Bundle`, `TranslationKey → Phrase`,
     `Translation → Message`, `Contributor → Member`. Statuses map
     `untranslated → open`, `translated → draft`, `needs_review → pending_review`, `approved → approved`.
   - The `TranslationSaved` event becomes `MessageSaved`; `ImportCompleted` becomes `ImportFinished`.
   - If you must preserve primary keys/timestamps instead of re-importing, copy table-to-table using the
     map above; otherwise prefer the re-import.

**Coming from the pro package (`outhebox/laravel-translations-pro`)**
   - Pro tables map onto the support tables: `revisions → tx_revisions`,
     `activity_logs → tx_activities`, `ai_usage_logs → tx_ai_usages`,
     `glossary_terms → tx_terms`, `glossary_translations → tx_term_definitions`,
     `validation_issues → tx_quality_issues`, `key_contexts → tx_phrase_usages`,
     `hardcoded_strings → tx_loose_strings`, `hardcoded_ignores → tx_ignored_strings`.
   - AI config moves from `translations-pro.php` into the `ai`, `quality` and `scanning` sections of the
     single `translations.php`. Set `TRANSLATIONS_AI=true` and your provider/key to re-enable AI.
   - History and glossary are best re-imported from a fresh scan/translation pass; only revision history
     is non-reproducible, so copy `tx_revisions` table-to-table if you need to keep it.

3. **Remove the old packages** once verified:

   ```bash
   composer remove outhebox/laravel-translations outhebox/laravel-translations-pro
   ```

---

## Installation

```bash
composer require syriable/laravel-translations
php artisan translations:install --import
```

AI features are optional and only require the SDK when you use them:

```bash
composer require laravel/ai
```

## Artisan commands

```
translations:install            Publish config, migrate, optionally --import
translations:import             Lang files -> DB        (--fresh, --no-overwrite)
translations:export             DB -> lang files        (--locale=, --bundle=)
translations:status             Coverage per locale/bundle (--locale=, --bundles, --bundle=)
translations:translate {locale} AI translate            (--key=, --all, --provider=, --queue)
translations:validate           Run quality checks      (--locale=, --fix, --queue)
translations:scan-usage         Record where keys are used (--path=, --queue)
translations:scan-loose         Detect hardcoded strings   (--path=, --queue)
translations:prune-revisions    Prune old history       (--days=, --dry-run)
```

## License

MIT.
