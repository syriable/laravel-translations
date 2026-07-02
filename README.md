# Laravel Translations

[![Tests](https://github.com/syriable/laravel-translations/actions/workflows/tests.yml/badge.svg)](https://github.com/syriable/laravel-translations/actions/workflows/tests.yml)
[![Lint](https://github.com/syriable/laravel-translations/actions/workflows/lint.yml/badge.svg)](https://github.com/syriable/laravel-translations/actions/workflows/lint.yml)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE.md)

Manage your Laravel translations from code — import and export language files, machine-translate with
AI, enforce quality, track revision history, detect hardcoded strings, manage a glossary, and report
analytics. It's a **backend-only** toolkit: one config file, one service provider, one `Translations`
facade. No UI, no frontend, no opinions about how you render anything.

```php
use Syriable\Translations\Facades\Translations;

Translations::import();                            // lang files  → database
Translations::set('auth.failed', 'Echec', 'fr');   // write a value
Translations::get('auth.failed', 'fr');            // 'Echec'
Translations::translate('auth.failed', 'de');      // AI translation
Translations::export();                            // database → lang files
```

---

## Table of contents

- [Installation](#installation)
- [Configuration](#configuration)
- [How it's modelled](#how-its-modelled)
- [Usage](#usage)
  - [Reading and writing translations](#reading-and-writing-translations)
  - [Locales](#locales)
  - [Importing and exporting language files](#importing-and-exporting-language-files)
  - [AI translation](#ai-translation)
  - [Quality checks](#quality-checks)
  - [Validation rules](#validation-rules)
  - [Glossary](#glossary)
  - [Revision history](#revision-history)
  - [Review workflow](#review-workflow)
  - [Analytics](#analytics)
  - [Scanning your source code](#scanning-your-source-code)
  - [Activity log](#activity-log)
- [API reference](#api-reference)
- [Artisan commands](#artisan-commands)
- [Configuration reference](#configuration-reference)
- [Events](#events)
- [Models](#models)
- [Enums](#enums)
- [Testing](#testing)
- [Security](#security)
- [Contributing](#contributing)
- [Changelog](#changelog)
- [License](#license)

---

## Installation

Require the package via Composer:

```bash
composer require syriable/laravel-translations
```

Run the installer — it publishes the config file, runs the migrations, and (with `--import`) loads
your existing language files:

```bash
php artisan translations:install --import
```

Or do the steps yourself:

```bash
php artisan vendor:publish --tag=translations-config
php artisan vendor:publish --tag=translations-migrations   # optional, only to customize them
php artisan migrate
```

**Requirements:** PHP 8.3+, Laravel 12 or 13.

**AI features are optional.** They're only needed when you call `Translations::translate()` and friends,
and they rely on the [`laravel/ai`](https://github.com/laravel/ai) SDK:

```bash
composer require laravel/ai
```

---

## Configuration

Everything is driven by `config/translations.php`. The two settings you'll touch first:

```php
'source_locale' => env('TRANSLATIONS_SOURCE_LOCALE', 'en'),  // the language you author in
'lang_path'     => env('TRANSLATIONS_LANG_PATH', lang_path()), // where import reads / export writes
```

All tables are prefixed (default `tx_`) and can live on a dedicated connection, so the package never
clashes with your schema:

```php
'database' => [
    'connection' => env('TRANSLATIONS_DB_CONNECTION'),
    'prefix'     => env('TRANSLATIONS_DB_PREFIX', 'tx_'),
],
```

See the [configuration reference](#configuration-reference) for every key.

---

## How it's modelled

Four core records describe your catalog:

| Record | Table | What it is |
| --- | --- | --- |
| **Locale** | `tx_locales` | A language (`en`, `es`, …). Exactly one is the **source** (`is_source = true`). |
| **Bundle** | `tx_bundles` | A language file — `auth.php`, the JSON bundle (`_json`), or a vendor namespace. |
| **Phrase** | `tx_phrases` | A translation key within a bundle (e.g. `failed`, `nested.key`), with detected placeholders/HTML/plural flags. |
| **Message** | `tx_messages` | The translated value of one phrase in one locale, with a [status](#enums). |

A dotted key like `auth.failed` resolves to bundle `auth` + phrase `failed`. Nested lang files use
their slash path as the bundle name (`filament/resources/bundle-resource.title`), matching Laravel's
own group convention. Keys with no dot live in the JSON bundle (`_json`).

Every message carries a **status** (`open` → `draft`/`pending_review` → `approved`). Writing a value
fires a `MessageSaved` event that records a revision, runs quality checks, and flushes analytics.

---

## Usage

Everything is reachable from the `Translations` facade, or by resolving the underlying classes from
the container (`app(TranslationManager::class)`, `app(Inspector::class)`, …). The examples use the
facade.

### Reading and writing translations

```php
use Syriable\Translations\Facades\Translations;

// Read a value for a locale (defaults to the source locale when omitted)
Translations::get('cart.checkout', 'es');     // 'Pagar' or null
Translations::has('cart.checkout', 'es');     // bool

// Write a value — creates the phrase (and seeds every locale) on demand
Translations::set('cart.checkout', 'Pagar', 'es');

// With metadata recorded on the revision
Translations::set('cart.checkout', 'Pagar', 'es', [
    'by'     => 'maria',                          // author, stored on the revision
    'reason' => 'manual',                         // manual|import|ai|rollback|bulk
    'status' => \Syriable\Translations\Enums\MessageStatus::Approved,
    'meta'   => ['source' => 'support-ticket'],
]);

// Clear a single locale's value (keeps the phrase), or delete the phrase entirely
Translations::forget('cart.checkout', 'es');   // value → null, status → open
Translations::forget('cart.checkout');         // deletes the phrase across all locales

// Every value for a locale, keyed by dotted key
Translations::all('es');                       // ['cart.checkout' => 'Pagar', ...]
```

`set()` is wrapped in a database transaction and returns the saved `Message`.

### Finding similar keys

Surface phrases in the **same bundle** that share a leading key segment — handy for spotting related
rules while you work (e.g. `validation.accepted` and its `accepted_if` / `accepted_unless` cousins).
Segments are split on `.`, `_` and `-`.

```php
Translations::similar('validation.accepted');
// Collection<Phrase>: accepted_if, accepted_unless, ...

Translations::similar('validation.accepted')->map->dottedKey();
// ['validation.accepted_if', 'validation.accepted_unless']

// Options
Translations::similar('validation.accepted', [
    'segments'     => 1,      // how many leading segments must match (default 1)
    'limit'        => 5,      // cap the number of results
    'include_self' => false,  // keep the given phrase in the result (default false)
]);
```

It returns a `Collection<Phrase>` from the same bundle only, ordered by key, excluding the given
phrase by default. An unknown key yields an empty collection.

### Locales

```php
Translations::locales();                       // Collection<Locale>, ordered by code

// Add a target locale — metadata (name, native name, RTL) is auto-detected and every
// existing phrase is seeded an "open" message for it
Translations::addLocale('de');

// Mark a locale as the source
Translations::addLocale('en', ['is_source' => true]);
```

### Importing and exporting language files

Import reads PHP (including nested directories), JSON and vendor namespace files from `lang_path`
into the database. The whole import runs in a transaction and suppresses per-row events, so a large
or `--fresh` import never bloats history or leaves a half-written catalog behind.

```php
$summary = Translations::import();                       // returns an ImportSummary
$summary = Translations::import(['fresh' => true]);      // clear everything first
$summary = Translations::import(['overwrite' => false]); // keep existing values

$summary->localeCount;  // 3
$summary->phraseCount;  // 412
$summary->createdCount; // 1180
$summary->updatedCount; // 0
```

Export writes the database back to disk, preserving nesting, key sorting, plurals and Unicode:

```php
Translations::export();                          // everything
Translations::export(['locale' => 'es']);        // one locale
Translations::export(['bundle' => 'auth']);      // one bundle
```

Set `export.approved_only` in config to export only reviewer-approved strings.

> Imports are the catalog's source of truth and skip per-row quality/revisions; run
> `php artisan translations:validate` afterwards to check imported strings.

### AI translation

Requires `laravel/ai` and `ai.enabled = true`. Translations are produced through a swappable
`Translator` contract, so the engine can be faked in tests with no HTTP.

```php
// Translate one key into a locale (writes the result, marks it ai_generated)
Translations::translate('cart.checkout', 'de');

// The AI service for finer control
$ai = Translations::ai();   // MachineTranslation

$ai->translateOpen($germanLocale);               // translate every untranslated message; returns count
$ai->suggest($phrase, $germanLocale, ['variants' => 3]); // TranslationResult, without saving
$ai->apply($phrase, $germanLocale, [             // translate + save the best variant
    'tone'     => 'formal',                       // neutral|formal|informal|friendly|technical
    'glossary' => true,                           // include matching glossary terms in the prompt
    'context'  => true,                           // include developer note, usages and sibling keys
    'provider' => 'anthropic',                    // validated against ai.allowed_providers
    'by'       => 'ai-bot',
]);
$ai->estimate($phraseIds, 'de');                 // ['phrase_count','target_locale','estimated_cost']
```

Prompts are context-aware (glossary terms, developer notes, where the key is used, sibling keys) and
**fence untrusted context** so it can't act as instructions. Context is on by default; pass
`'context' => false` (or set `ai.context` to `false`) for a leaner, cheaper prompt — useful on large
batch runs. Every call is logged to `tx_ai_usages` with an estimated cost from `ai.cost_rates`.

To discover which providers are actually usable — allowlisted **and** configured with a key in `.env`
(via the `laravel/ai` config) — call `AiProviders::usable()`. It returns a `Collection<AiProvider>`
(Ollama is treated as usable without a key, since it authenticates via its URL):

```php
use Syriable\Translations\Ai\AiProviders;

AiProviders::usable();                 // Collection<AiProvider> — e.g. [OpenAI, Anthropic]
AiProviders::usable()->map->getLabel(); // ['OpenAI', 'Anthropic']
```

Each suggestion in the returned `TranslationResult` carries a `value` (the translation as proposed), a
`base_value` (the **clean, copy/store-ready** translation — the exact string to write to a language
file, stripped of any framing the model may add such as `Translate to German, for example: "…"`), a
`confidence` score, a `recommended` flag (exactly one variant is always recommended), and an optional
`note` — a concise, human-readable explanation of why that wording was chosen (terminology, common
usage, natural phrasing, framework conventions), written in the **source language** so the translator
reading it understands the reasoning. Read the recommended variant directly:

```php
$result = $ai->suggest($phrase, $germanLocale, ['variants' => 3]);

$result->best();        // the clean translation to store/copy (from base_value)
$result->proposed();    // the translation exactly as the model phrased it (may include framing)
$result->note();        // why it was chosen, e.g. "Standard term used in German UIs."
$result->recommended(); // the full recommended variant array
```

`base_value` comes from a dedicated structured-output field the model fills, and is cleaned defensively
(surrounding quotes stripped, and the quoted translation lifted out of an `e.g. "…"` example) so a
"copy" button always gets just the translation. `apply()` stores `best()`, so the value written to your
catalog is the clean one even when the model wraps its answer in commentary.

**Swapping the engine** (e.g. in tests) is a one-liner — `Translator` has a single method:

```php
use Syriable\Translations\Contracts\Translator;
use Syriable\Translations\Ai\FakeTranslator;

$this->app->instance(Translator::class, new FakeTranslator(
    fn ($request) => strtoupper($request->text),
));
```

### Quality checks

Ten pluggable checks compare each translation against its source. They run automatically on every
save (when `quality.run_on_save` is on) and on demand.

```php
$quality = Translations::quality();   // Inspector

$quality->scan();                     // check every translated message; returns a stats array
$quality->scan($localeId);            // limit to one locale
// ['error' => 2, 'warning' => 5, 'info' => 1, 'checked' => 412]

$quality->inspect($message);          // array<Issue>, without persisting
$quality->inspectAndStore($message);  // persist QualityIssue rows for one message
$quality->fix($qualityIssue);         // auto-fix a fixable issue (whitespace, casing); returns bool
```

| Check | Severity | Auto-fix |
| --- | --- | --- |
| `missing_placeholder` — a `:name`/`{count}` from the source is missing | error | — |
| `unexpected_placeholder` — a placeholder not in the source | warning | — |
| `plural` — the translation's plural selectors/segments don't match the source | error | — |
| `inconsistent_plural_selector` — the source plural mixes selectored (`{0}`, `[1,19]`) and selectorless segments | warning | — |
| `html_tag_mismatch` — HTML tags differ from the source | error | — |
| `length_ratio` — translation length is outside the expected band | warning | — |
| `whitespace` — leading/trailing whitespace differs | warning | ✅ |
| `casing` — first-letter capitalization differs | info | ✅ |
| `url_email` — a URL or email was altered/dropped | error | — |
| `glossary` — a glossary term wasn't applied | warning | — |

Disable a check by removing its class from `quality.checks`, or add your own implementing
`Syriable\Translations\Contracts\QualityCheck`.

### AI quality review

The deterministic checks above catch *mechanical* problems (placeholders, HTML, plural selectors).
For the *linguistic* ones they can't see — unnatural phrasing, gender issues, pluralization errors,
context mismatches and cross-key inconsistencies — the package ships an **AI reviewer**. Like AI
translation it requires `laravel/ai` and `ai.enabled = true`, and runs through a swappable `Reviewer`
contract so it can be faked in tests with no HTTP.

```php
$review = Translations::aiReview();          // MachineReview

$result = $review->review($germanLocale);    // ReviewResult — reviews every translated message
$review->review($germanLocale, [
    'phrase_ids' => [1, 2, 3],                // limit the review to specific phrases
    'provider'   => 'anthropic',             // validated against ai.allowed_providers
]);
```

It gathers the source/target pairs for the locale's translated messages, sends them to the model in
batches (`ai.review.batch_size`, default 50) and returns a `ReviewResult`:

```php
$result->hasIssues();          // bool
$result->issues;               // array<ReviewIssue>{ key, severity, description, suggestion, baseSuggestion }
$result->forKey('cart.checkout'); // the issues reported for one dotted key
$result->countsBySeverity();   // ['high' => 1, 'medium' => 3, 'low' => 0]
```

Each `ReviewIssue` carries the dotted `key` it refers to, a `severity` as a dedicated
`ReviewSeverity` (`Low`, `Medium`, `High` — the reviewer's own priority scale, distinct from the
deterministic checks' `Severity`), a `description` of the problem (in the **source language**), an
optional `suggestion` explaining the fix, and — when the reviewer proposes a corrected translation — a
`baseSuggestion`: that correction on its own, in the target language, cleaned of any framing so it can
be copied straight into the catalog (same treatment as a translation suggestion's `base_value`). The
reviewer **fences untrusted source/target text** so it can't
act as instructions, drops any issue the model invents for a key that wasn't reviewed, and logs every
batch to `tx_ai_usages` with an estimated cost. Unlike the deterministic checks it does **not** persist
`QualityIssue` rows — it's an on-demand review you run before approving a batch.

Run it from the CLI (exits non-zero when high-priority issues are found, so it slots into CI):

```bash
php artisan translations:ai-review de --provider=anthropic
```

**Swapping the engine** (e.g. in tests) is a one-liner — `Reviewer` has a single method:

```php
use Syriable\Translations\Contracts\Reviewer;
use Syriable\Translations\Ai\FakeReviewer;
use Syriable\Translations\Enums\ReviewSeverity;
use Syriable\Translations\Support\ReviewIssue;

$this->app->instance(Reviewer::class, new FakeReviewer(
    fn ($request) => [new ReviewIssue('cart.checkout', ReviewSeverity::Medium, 'Too informal.', 'Use the formal register.')],
));
```

### Validation rules

For validating a translation value *as it's submitted* (e.g. in a form request or Livewire component),
the package ships two reusable Laravel `ValidationRule`s under `Syriable\Translations\Rules`. Each takes
the `Phrase` the value belongs to, so it can compare against the source and the phrase's metadata:

```php
use Syriable\Translations\Rules\TranslationPlaceholdersRule;
use Syriable\Translations\Rules\TranslationPluralRule;

$request->validate([
    'value' => [
        'required',
        new TranslationPlaceholdersRule($phrase),  // every :name / {count} the key defines is present
        new TranslationPluralRule($phrase),        // plural selectors/segments match the source
    ],
]);
```

- **`TranslationPlaceholdersRule`** — fails when the value drops a placeholder the phrase defines. Skips
  empty values and phrases with no placeholders. Its detection logic is reusable directly:
  `TranslationPlaceholdersRule::missingPlaceholders($phrase, $value)` returns the missing placeholders.
- **`TranslationPluralRule`** — for plural phrases, requires the value to match the source: when the
  source uses explicit selectors (`{0}`, `[1,19]`, `[20,*]`) every selector must be preserved exactly,
  otherwise the pipe-separated variant count must match. Skips empty values and non-plural phrases.

These complement the [quality checks](#quality-checks): the rules block bad input up front, while the
checks audit already-stored translations.

### Glossary

Per-locale approved terminology that feeds both AI prompts and the glossary quality check.

```php
$glossary = Translations::glossary();   // Glossary

$term = $glossary->define('invoice', note: 'billing document', wholeWord: true);
$glossary->translate($term, $spanishLocale->id, 'factura', approvedBy: 'maria');

$glossary->pairsFor('Download your invoice', $spanishLocale->id);  // ['invoice' => 'factura']
$glossary->matching('Download your invoice', $spanishLocale->id);  // Collection<Term>
$glossary->forget($term);
```

### Revision history

Every value change is recorded in `tx_revisions`. Roll a message back, or undo a batch of changes.

```php
$revisions = Translations::revisions();   // RevisionRollback

$revisions->toRevision($revision);                       // restore a message to a past revision
$revisions->byMember('maria');                           // undo every change a contributor made
$revisions->byMember('maria', from: '2026-01-01', to: '2026-02-01');
$revisions->afterDate('2026-06-01', localeId: $es->id);  // undo changes after a cutoff
```

Prune old history with `php artisan translations:prune-revisions` (keeps the latest per message).

### Review workflow

When `review.enabled` is on, non-reviewer saves land in `pending_review` instead of `approved`.

```php
$review = Translations::review();   // ReviewFlow

use Syriable\Translations\Enums\MemberRole;

$review->statusForSave(MemberRole::Translator);   // MessageStatus::PendingReview
$review->statusForSave(MemberRole::Reviewer);     // MessageStatus::Approved

$review->approve($message, $member->id);
$review->reject($message, 'Too informal', $member->id);
$review->requestReview($message, $member->id);
```

> The actor string is **advisory** — it's recorded, not enforced. Authorization is your application's
> job; gate access with `MemberRole` or your own policies. See [Security](#security).
>
> Pass a `Member` **ulid**, not your host app's own user id — the activity log's `member()` relation
> (see [Activity log](#activity-log)) resolves against `tx_members.id`. Use `Member::current()` to look
> up the member matching the authenticated app user (matched by email) instead of `auth()->id()`.

Rejecting also records the note as a comment (see below), so it shows up in the activity feed alongside
the status change.

### Analytics

```php
$insights = Translations::insights();   // Insights

$insights->dashboard();          // cached bundle of everything below
$insights->coverage();           // per-locale: total / translated / approved / percent
$insights->bundleCoverage();     // per-bundle progress (per lang file)
$insights->overallCoverage();    // single float across all target locales
$insights->leaderboard();        // top contributors by change count
$insights->velocity(days: 30);   // changes per day
$insights->stale($localeId);     // messages older than analytics.stale_after_days
$insights->flush();              // drop the cache (also flushed automatically on writes/imports)
```

### Scanning your source code

```php
// Record where each translation key is used (for richer AI context)
Translations::scanUsage();              // scans config('translations.scanning.paths')
Translations::scanUsage('resources/views');

// Detect hardcoded, untranslated strings (false-positive filter + ignore list)
Translations::scanLoose();
```

Both return a stats array. Run them in the background with the `--queue` flag on the corresponding
commands, or set `scanning.scan_after_import` to queue a usage scan after every import.

### Activity log

Status changes, review requests, and comments on a message are recorded automatically into `tx_activities`
(toggle with `activities.enabled`):

```php
$message->activities;                                  // Collection<Activity>, newest last
$message->activities()->latest('id')->get();

$activity->action;      // 'status_changed' | 'review_requested' | 'comment_added'
$activity->member_id;   // whoever performed the action
$activity->member;      // BelongsTo Member (resolves if member_id is a Member ulid)
$activity->meta;        // action-specific payload, e.g. ['from' => 'draft', 'to' => 'approved']
```

Comments (e.g. reviewer feedback) live in their own `tx_comments` table and thread per message:

```php
$message->comment('Please double check this wording', $member->id);
$message->comments;   // Collection<Comment>
```

`ReviewFlow::reject()` posts its `$note` as a comment automatically, so rejection feedback shows up both
as a `comment_added` activity and in `$message->comments()`.

`ActivityRecorder` is also available directly for auditing arbitrary, non-message actions:

```php
use Syriable\Translations\Support\ActivityRecorder;

app(ActivityRecorder::class)->log('glossary.updated', $term, ['field' => 'value'], memberId: $member->id);
```

---

## API reference

Every method on the `Translations` facade (backed by `TranslationManager`):

| Method | Returns | Description |
| --- | --- | --- |
| `get(string $key, ?string $locale = null)` | `?string` | Value for a key, or `null`. |
| `has(string $key, ?string $locale = null)` | `bool` | Whether a value exists. |
| `set(string $key, string $value, ?string $locale = null, array $options = [])` | `Message` | Write a value (transactional); creates the phrase on demand. Options: `by`, `reason`, `status`, `meta`. |
| `forget(string $key, ?string $locale = null)` | `void` | Clear one locale's value, or delete the phrase. |
| `all(?string $locale = null)` | `array` | All values for a locale, keyed by dotted key. |
| `similar(string $key, array $options = [])` | `Collection` | Phrases in the same bundle sharing a leading key segment. Options: `segments`, `limit`, `include_self`. |
| `locales()` | `Collection` | All locales. |
| `addLocale(string $code, array $attributes = [])` | `Locale` | Create a locale and seed its messages. |
| `import(array $options = [])` | `ImportSummary` | Disk → DB. Options: `fresh`, `overwrite`, `lang_path`, `source`, `triggered_by`. |
| `export(array $options = [])` | `ExportSummary` | DB → disk. Options: `locale`, `bundle`, `lang_path`, `source`. |
| `translate(string $key, string $locale, array $options = [])` | `?Message` | AI-translate a single key and save it. |
| `ai()` | `MachineTranslation` | The AI translation service. |
| `aiReview()` | `MachineReview` | The AI quality-review service. |
| `quality()` | `Inspector` | The quality-check service. |
| `glossary()` | `Glossary` | The glossary service. |
| `insights()` | `Insights` | The analytics service. |
| `revisions()` | `RevisionRollback` | Revision rollback service. |
| `review()` | `ReviewFlow` | The review/approval service. |
| `scanUsage(?string $path = null)` | `array` | Scan source for key usages. |
| `scanLoose(?string $path = null)` | `array` | Detect hardcoded strings. |

The sub-services:

| Service | Methods |
| --- | --- |
| `MachineTranslation` | `suggest(Phrase, Locale, array)`, `apply(Phrase, Locale, array)`, `translateOpen(Locale, array): int`, `estimate(array $phraseIds, string $locale): array` |
| `MachineReview` | `review(Locale, array): ReviewResult` |
| `Inspector` | `inspect(Message): array`, `inspectAndStore(Message): array`, `scan(?int $localeId): array`, `fix(QualityIssue): bool` |
| `Glossary` | `define(string, ?string, bool, bool, ?string): Term`, `translate(Term, int, string, ?string): TermDefinition`, `forget(Term)`, `matching(string, int): Collection`, `pairsFor(string, int): array` |
| `RevisionRollback` | `toRevision(Revision, ?string): Message`, `byMember(string, ?string $from, ?string $to, ?string $by): array`, `afterDate(string, ?int $localeId, ?string $by): array` |
| `Insights` | `dashboard()`, `coverage()`, `bundleCoverage(?string)`, `overallCoverage(): float`, `leaderboard()`, `velocity(int $days = 30)`, `stale(?int)`, `staleCounts()`, `flush()` |
| `ReviewFlow` | `statusForSave(?MemberRole): MessageStatus`, `approve(Message, ?string): Message`, `reject(Message, string $note, ?string): Message` |

---

## Artisan commands

```
translations:install            Publish config, migrate, optionally --import
translations:import             Lang files → DB           (--fresh, --no-overwrite)
translations:export             DB → lang files           (--locale=, --bundle=)
translations:status             Coverage per locale/bundle (--locale=, --bundles, --bundle=)
translations:translate {locale} AI translate              (--key=, --all, --provider=, --queue)
translations:validate           Run quality checks        (--locale=, --fix, --queue)
translations:ai-review {locale}  AI-review translation quality (--provider=)
translations:scan-usage         Record where keys are used (--path=, --queue)
translations:scan-loose         Detect hardcoded strings   (--path=, --queue)
translations:prune-revisions    Prune old revision history (--days=, --dry-run)
```

---

## Configuration reference

```php
return [
    // The language you author in; every other locale is a translation target.
    'source_locale' => env('TRANSLATIONS_SOURCE_LOCALE', 'en'),

    // Where import reads and export writes.
    'lang_path' => env('TRANSLATIONS_LANG_PATH', lang_path()),

    // Package tables are prefixed and may use a dedicated connection.
    'database' => [
        'connection' => env('TRANSLATIONS_DB_CONNECTION'),
        'prefix'     => env('TRANSLATIONS_DB_PREFIX', 'tx_'),
    ],

    'import' => [
        'scan_vendor'         => true,   // import vendor/<ns>/<locale>/*.php
        'detect_placeholders' => true,   // extract :name and {count}
        'detect_html'         => true,
        'detect_plural'       => true,
        'exclude_files'       => ['pagination.php'],
    ],

    'export' => [
        'sort_keys'     => true,
        'exclude_empty' => true,
        'approved_only' => env('TRANSLATIONS_EXPORT_APPROVED_ONLY', false),
    ],

    'review' => [
        'enabled' => env('TRANSLATIONS_REVIEW', true),
    ],

    'revisions' => [
        'enabled'        => true,
        'retention_days' => 90,
    ],

    'ai' => [
        'enabled'           => env('TRANSLATIONS_AI', false),
        'provider'          => env('TRANSLATIONS_AI_PROVIDER', 'openai'),
        'model'             => env('TRANSLATIONS_AI_MODEL', 'gpt-4o-mini'),
        'allowed_providers' => array_column(AiProvider::cases(), 'value'), // Syriable\Translations\Enums\AiProvider
        'variants'          => 3,
        'batch_size'        => 20,
        'review'            => ['batch_size' => 50], // source/target pairs per AI quality-review request
        'context'           => true, // include note/usages/siblings in the prompt (per-call overridable)
        'cost_rates'        => [ /* model => ['input' => ..., 'output' => ...] in USD per 1M chars */ ],
    ],

    'quality' => [
        'run_on_save'  => true,
        'checks'       => [ /* the eight QualityCheck classes */ ],
        'length_ratio' => ['min' => 0.5, 'max' => 2.0, 'overrides' => []],
    ],

    'scanning' => [
        'paths'             => ['app', 'resources/views', 'resources/js'],
        'extensions'        => ['php', 'blade.php', 'vue', 'jsx', 'tsx'],
        'scan_after_import' => env('TRANSLATIONS_SCAN_AFTER_IMPORT', false),
        'loose'             => ['min_words' => 2, 'min_length' => 5],
    ],

    'analytics' => [
        'cache_ttl'        => 3600,
        'stale_after_days' => 30,
        'leaderboard_limit' => 10,
    ],

    'queue' => [
        'connection' => env('TRANSLATIONS_QUEUE_CONNECTION'),
        'name'       => env('TRANSLATIONS_QUEUE_NAME', 'translations'),
    ],
];
```

---

## Events

Hook into the translation lifecycle with standard Laravel listeners:

| Event | Fired when | Payload |
| --- | --- | --- |
| `MessageSaved` | a message's value changes | `$message`, `$oldValue`, `$reason`, `$changedBy`, `$meta` |
| `MessageStatusChanged` | a message's status changes | `$message`, `$oldStatus`, `$newStatus`, `$reason`, `$changedBy`, `$meta` |
| `CommentPosted` | a comment is created via `Message::comment()` | `$comment` |
| `ImportFinished` | an import completes | `$summary` (`ImportSummary`) |
| `ExportFinished` | an export completes | `$summary` (`ExportSummary`) |
| `PhraseCreated` | a new phrase is created via the API | `$phrase` |
| `LocaleAdded` | a new locale is added | `$locale` |

Internally, `MessageSaved` drives `RecordRevision`, `RunQualityChecks` and `FlushInsightsCache`;
`MessageStatusChanged` drives `RecordStatusActivity`; `CommentPosted` drives `RecordCommentActivity`;
`ImportFinished` drives `ScanUsageAfterImport` (when enabled) and a cache flush.

---

## Models

All models live in `Syriable\Translations\Models` and use the configured table prefix.

| Model | Notable relations / methods |
| --- | --- |
| `Locale` | `messages()`, `members()`; scopes `enabled()`, `targets()`; static `source()`, `flushSourceCache()` |
| `Bundle` | `phrases()`; `isJson()`, `label()`; scope `withTranslationProgress()`, `translationProgressPercent()` |
| `Phrase` | `bundle()`, `messages()`, `usages()`; `dottedKey()`; scope `missingIn(int $localeId)` |
| `Message` | `phrase()`, `locale()`, `revisions()`, `issues()`, `comments()`, `activities()`; scopes `translated()`, `open()`, `pendingReview()`; static `stamp()`, `clearStamp()`, `withStamp()`; `comment(string, ?string, array)` |
| `Member` | `locales()`; `role` cast to `MemberRole`; static `current()` — resolves the `Member` matching the authenticated app user by email |
| `Revision` | `message()`; scopes `forLocale(int)`, `between(?string, ?string)` |
| `QualityIssue` | `message()`, `locale()`; `severity` cast to `Severity` |
| `Term` / `TermDefinition` | `definitions()` / `term()`, `locale()`; `definitionFor(int $localeId)` |
| `Activity` | `subject()` (morph), `member()`; `action`, `meta` (array) |
| `Comment` | `message()`, `member()`; `body`, `meta` (array) |
| `AiUsage`, `PhraseUsage`, `LooseString`, `IgnoredString`, `ImportRecord`, `ExportRecord` | audit / support records |

---

## Enums

In `Syriable\Translations\Enums`:

- **`MessageStatus`** — `Open`, `Draft`, `PendingReview`, `Approved`. Methods: `label()`, `isTranslated()`.
- **`MemberRole`** — `Owner`, `Admin`, `Reviewer`, `Translator`, `Viewer`. Methods: `level()`, `isAtLeast()`, `canTranslate()`, `canReview()`, `canManage()`.
- **`RevisionReason`** — `Manual`, `Import`, `Ai`, `Rollback`, `Bulk`. Method: `label()`.
- **`Severity`** — `Error`, `Warning`, `Info` (deterministic quality checks). Method: `order()`.
- **`ReviewSeverity`** — `Low`, `Medium`, `High` (AI quality-review priority). Methods: `order()`, `fromModel()`.
- **`LooseStringStatus`** — `Pending`, `Converted`, `Ignored`, `Resolved`.

---

## Testing

```bash
composer test          # vendor/bin/pest
vendor/bin/pint        # code style
```

The suite runs on Pest 4 + Orchestra Testbench against in-memory SQLite. AI paths are tested through
the `Translator` contract with `FakeTranslator`, so no test makes a network call.

---

## Security

This is a backend-only toolkit with no auth layer; a few responsibilities sit with the consuming
application (importing `.php` lang files executes them, models are mass-assignable, AI output is
untrusted, authorization is yours). Read [`SECURITY.md`](SECURITY.md) for the full trust model and
how to report a vulnerability.

## Contributing

See [`CONTRIBUTING.md`](CONTRIBUTING.md). Run `composer test` and `vendor/bin/pint --test` before
opening a PR.

## Changelog

See [`CHANGELOG.md`](CHANGELOG.md).

## License

The MIT License (MIT). See [`LICENSE.md`](LICENSE.md).
