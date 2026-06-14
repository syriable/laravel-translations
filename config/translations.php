<?php

use Syriable\Translations\Quality\Checks\CasingCheck;
use Syriable\Translations\Quality\Checks\GlossaryCheck;
use Syriable\Translations\Quality\Checks\HtmlTagCheck;
use Syriable\Translations\Quality\Checks\LengthRatioCheck;
use Syriable\Translations\Quality\Checks\MissingPlaceholderCheck;
use Syriable\Translations\Quality\Checks\UnexpectedPlaceholderCheck;
use Syriable\Translations\Quality\Checks\UrlEmailCheck;
use Syriable\Translations\Quality\Checks\WhitespaceCheck;

return [

    'source_locale' => env('TRANSLATIONS_SOURCE_LOCALE', 'en'),

    'lang_path' => env('TRANSLATIONS_LANG_PATH', lang_path()),

    'database' => [
        'connection' => env('TRANSLATIONS_DB_CONNECTION'),
        'prefix' => env('TRANSLATIONS_DB_PREFIX', 'tx_'),
    ],

    'import' => [
        'scan_vendor' => true,
        'detect_placeholders' => true,
        'detect_html' => true,
        'detect_plural' => true,
        'exclude_files' => ['pagination.php'],
    ],

    'export' => [
        'sort_keys' => true,
        'exclude_empty' => true,
        'approved_only' => env('TRANSLATIONS_EXPORT_APPROVED_ONLY', false),
    ],

    'review' => [
        'enabled' => env('TRANSLATIONS_REVIEW', true),
    ],

    'revisions' => [
        'enabled' => true,
        'retention_days' => 90,
    ],

    'ai' => [
        'enabled' => env('TRANSLATIONS_AI', false),
        'provider' => env('TRANSLATIONS_AI_PROVIDER', 'openai'),
        'model' => env('TRANSLATIONS_AI_MODEL', 'gpt-4o-mini'),
        'variants' => 3,
        'batch_size' => 20,
        'cost_rates' => [
            'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
            'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
            'claude-sonnet-4-5' => ['input' => 3.00, 'output' => 15.00],
        ],
    ],

    'quality' => [
        'run_on_save' => true,
        'checks' => [
            MissingPlaceholderCheck::class,
            UnexpectedPlaceholderCheck::class,
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

    'scanning' => [
        'paths' => ['app', 'resources/views', 'resources/js'],
        'extensions' => ['php', 'blade.php', 'vue', 'jsx', 'tsx'],
        'scan_after_import' => env('TRANSLATIONS_SCAN_AFTER_IMPORT', false),
        'loose' => [
            'min_words' => 2,
            'min_length' => 5,
        ],
    ],

    'analytics' => [
        'cache_ttl' => 3600,
        'stale_after_days' => 30,
        'leaderboard_limit' => 10,
    ],

    'queue' => [
        'connection' => env('TRANSLATIONS_QUEUE_CONNECTION'),
        'name' => env('TRANSLATIONS_QUEUE_NAME', 'translations'),
    ],

];
