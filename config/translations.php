<?php

use Syriable\Translations\Enums\AiProvider;
use Syriable\Translations\Quality\Checks\CasingCheck;
use Syriable\Translations\Quality\Checks\GlossaryCheck;
use Syriable\Translations\Quality\Checks\HtmlTagCheck;
use Syriable\Translations\Quality\Checks\InconsistentPluralSelectorCheck;
use Syriable\Translations\Quality\Checks\LengthRatioCheck;
use Syriable\Translations\Quality\Checks\MissingPlaceholderCheck;
use Syriable\Translations\Quality\Checks\PluralCheck;
use Syriable\Translations\Quality\Checks\UnexpectedPlaceholderCheck;
use Syriable\Translations\Quality\Checks\UrlEmailCheck;
use Syriable\Translations\Quality\Checks\WhitespaceCheck;

return [

    /*
    |--------------------------------------------------------------------------
    | Source locale
    |--------------------------------------------------------------------------
    |
    | The locale your application is written in. Every other locale is treated
    | as a target that gets translated from this one.
    |
    */

    'source_locale' => env('TRANSLATIONS_SOURCE_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Language files location
    |--------------------------------------------------------------------------
    |
    | Where your application's language files live on disk. Used when importing
    | from and exporting back to lang files.
    |
    */

    'lang_path' => env('TRANSLATIONS_LANG_PATH', lang_path()),

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    |
    | The connection used for the package tables and the prefix applied to
    | every table name so they never clash with your application tables.
    |
    */

    'database' => [
        'connection' => env('TRANSLATIONS_DB_CONNECTION'),
        'prefix' => env('TRANSLATIONS_DB_PREFIX', 'tx_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Member model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model that represents whoever is translating, reviewing or
    | managing translations in your application. Defaults to your app's own
    | user model, but can be swapped for any model. The package never owns a
    | table for it - it only stores the model's key as a plain string (e.g.
    | on activities, comments and the locale-assignment pivot) and, if the
    | model implements Syriable\Translations\Contracts\HasTranslationRole,
    | uses it to resolve a MemberRole for permission checks.
    |
    */

    'member_model' => env('TRANSLATIONS_MEMBER_MODEL', config('auth.providers.users.model', 'App\\Models\\User')),

    /*
    |--------------------------------------------------------------------------
    | Actor resolution
    |--------------------------------------------------------------------------
    |
    | Whenever a message is saved without an explicit "by" (who made the
    | change), the package asks Syriable\Translations\Contracts\ResolvesActor
    | to identify the actor instead - by default that's whoever is currently
    | authenticated on `auth_guard` (null uses your app's default guard).
    | This runs for manual edits, AI-triggered edits, reviews and rollbacks
    | alike, so `changed_by` / `translated_by` / `reviewed_by` get filled in
    | automatically. `system_actor` is what gets recorded when nobody is
    | authenticated (e.g. a queued job or console command) - left null by
    | default so unattended runs stay honestly unattributed. Bind your own
    | ResolvesActor implementation in the container to customize this (e.g.
    | to tag queue jobs with a service-account id).
    |
    */

    'auth_guard' => env('TRANSLATIONS_AUTH_GUARD'),

    'system_actor' => env('TRANSLATIONS_SYSTEM_ACTOR'),

    /*
    |--------------------------------------------------------------------------
    | Import
    |--------------------------------------------------------------------------
    */

    'import' => [
        'scan_vendor' => true,
        'detect_placeholders' => true,
        'detect_html' => true,
        'detect_plural' => true,
        'exclude_files' => ['pagination.php'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export
    |--------------------------------------------------------------------------
    */

    'export' => [
        'sort_keys' => true,
        'exclude_empty' => true,
        'approved_only' => env('TRANSLATIONS_EXPORT_APPROVED_ONLY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Review workflow
    |--------------------------------------------------------------------------
    |
    | When enabled, translations saved by non-reviewers land in a "pending
    | review" state instead of being approved immediately.
    |
    */

    'review' => [
        'enabled' => env('TRANSLATIONS_REVIEW', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Revision history
    |--------------------------------------------------------------------------
    */

    'revisions' => [
        'enabled' => true,
        'retention_days' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity log
    |--------------------------------------------------------------------------
    |
    | Records status changes, review requests and comments against messages
    | so they can be displayed as an activity feed.
    |
    */

    'activities' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | AI translation
    |--------------------------------------------------------------------------
    |
    | Powered by the laravel/ai SDK. `provider` and `model` choose the default
    | engine; `cost_rates` are USD per 1M characters and feed cost estimates.
    | Only providers in `allowed_providers` may be requested per-call.
    |
    */

    'ai' => [
        'enabled' => env('TRANSLATIONS_AI', false),
        'provider' => env('TRANSLATIONS_AI_PROVIDER', 'openai'),
        'model' => env('TRANSLATIONS_AI_MODEL', 'gpt-4o-mini'),
        'allowed_providers' => array_column(AiProvider::cases(), 'value'),
        'variants' => 3,
        'batch_size' => 20,

        // AI quality review (translations:ai-review). Translated messages are
        // sent to the model in batches of this many source/target pairs per
        // request to keep each prompt within token limits.
        'review' => [
            'batch_size' => 50,
        ],

        // Include per-phrase context (developer note, usage locations and
        // sibling keys) in the prompt. Improves quality; disable to send a
        // leaner, cheaper prompt. Overridable per call via the 'context' option.
        'context' => true,
        'cost_rates' => [
            'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
            'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
            'claude-sonnet-4-5' => ['input' => 3.00, 'output' => 15.00],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Quality checks
    |--------------------------------------------------------------------------
    |
    | The pluggable checks run against every saved translation (and on demand).
    | Remove a class to disable a check, or add your own implementing the
    | Syriable\Translations\Contracts\QualityCheck contract.
    |
    */

    'quality' => [
        'run_on_save' => true,
        'checks' => [
            MissingPlaceholderCheck::class,
            UnexpectedPlaceholderCheck::class,
            PluralCheck::class,
            InconsistentPluralSelectorCheck::class,
            HtmlTagCheck::class,
            LengthRatioCheck::class,
            WhitespaceCheck::class,
            CasingCheck::class,
            UrlEmailCheck::class,
            GlossaryCheck::class,
        ],
        'length_ratio' => [
            'min' => 0.5,
            'max' => 2.0,
            'overrides' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Source-code scanning
    |--------------------------------------------------------------------------
    |
    | `usage` powers context discovery (where each key is used) and `loose`
    | powers hardcoded-string detection. `scan_after_import` queues a usage
    | scan on the configured queue once an import finishes.
    |
    */

    'scanning' => [
        'paths' => ['app', 'resources/views', 'resources/js'],
        'extensions' => ['php', 'blade.php', 'vue', 'jsx', 'tsx'],
        'scan_after_import' => env('TRANSLATIONS_SCAN_AFTER_IMPORT', false),
        'loose' => [
            'min_words' => 2,
            'min_length' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    */

    'analytics' => [
        'cache_ttl' => 3600,
        'stale_after_days' => 30,
        'leaderboard_limit' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'connection' => env('TRANSLATIONS_QUEUE_CONNECTION'),
        'name' => env('TRANSLATIONS_QUEUE_NAME', 'translations'),
    ],

];
