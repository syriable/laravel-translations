<?php

declare(strict_types=1);

use Syriable\Translations\Extraction\Scanners\BladeScanner;
use Syriable\Translations\Extraction\Scanners\PhpScanner;
use Syriable\Translations\Validation\Rules\HtmlTagRule;
use Syriable\Translations\Validation\Rules\PlaceholderConsistencyRule;
use Syriable\Translations\Validation\Rules\PluralFormRule;

return [

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | The "source" locale is the language your keys and base strings are
    | authored in. It is the reference every other locale is measured and
    | validated against. Leave "available" empty to auto-discover locales
    | from the language path.
    |
    */

    'locales' => [
        'source' => env('TRANSLATIONS_SOURCE_LOCALE', 'en'),
        'available' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Language Path
    |--------------------------------------------------------------------------
    |
    | Where your translation catalog lives on disk. By default this is
    | Laravel's lang_path(). PHP group files, JSON files and vendor
    | namespaces underneath this directory are all understood.
    |
    */

    'lang_path' => env('TRANSLATIONS_LANG_PATH', lang_path()),

    /*
    |--------------------------------------------------------------------------
    | Extraction
    |--------------------------------------------------------------------------
    |
    | Extraction discovers translation *usages* in your source code. Each
    | scanner is responsible for a set of file extensions and produces the
    | keys it finds. Scanners are resolved from the container, so you may
    | add your own by implementing the Scanner contract.
    |
    */

    'extraction' => [
        'paths' => [
            app_path(),
            resource_path('views'),
        ],

        'exclude' => [
            'vendor',
            'node_modules',
            'storage',
        ],

        'scanners' => [
            PhpScanner::class,
            BladeScanner::class,
        ],

        // Translation helper functions recognised during extraction.
        'functions' => [
            '__',
            'trans',
            'trans_choice',
            '@lang',
            '@choice',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | The catalog is read and written through a storage driver. The "file"
    | driver works directly with your lang files and is the default. The
    | driver contract lets you ship custom drivers (e.g. database, remote).
    |
    */

    'storage' => [
        'default' => env('TRANSLATIONS_DRIVER', 'file'),

        'drivers' => [
            'file' => [
                'driver' => 'file',
                'path' => env('TRANSLATIONS_LANG_PATH', lang_path()),
            ],
        ],

        // Output formatting applied when writing the catalog back to disk.
        'output' => [
            'sort_keys' => true,
            'json_flags' => JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Synchronization
    |--------------------------------------------------------------------------
    |
    | Controls how the synchronizer reconciles extracted keys with the
    | catalog. When "fill_missing" is enabled, keys discovered in code but
    | absent from a locale are created (empty for non-source locales).
    |
    */

    'sync' => [
        'fill_missing' => true,
        'prune_unused' => false,
        'placeholder' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | Rules run against each translated value compared to its source value.
    | Add or remove rules freely; each implements the ValidationRule
    | contract and is resolved from the container.
    |
    */

    'validation' => [
        'rules' => [
            PlaceholderConsistencyRule::class,
            PluralFormRule::class,
            HtmlTagRule::class,
        ],

        // The plural form rule validates a translation against the number of
        // plural forms its own language uses (English 2, Polish 3, Arabic 6,
        // and so on). Built-in CLDR-based counts cover the common languages;
        // list a locale here to override or add one. Unknown languages are
        // skipped rather than flagged.
        'plural' => [
            'counts' => [
                // 'ar' => 6,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Analysis
    |--------------------------------------------------------------------------
    |
    | Health analysis compares the catalog against extracted usages. Keys
    | matching an "ignore" pattern are never reported as unused (useful for
    | keys referenced dynamically that extraction cannot see).
    |
    */

    'analysis' => [
        'ignore' => [
            // 'validation.*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Metadata
    |--------------------------------------------------------------------------
    |
    | Lang files remain the canonical source of truth for translation values.
    | Collaboration and analysis metadata (activity, revisions, comments,
    | contexts, …) that cannot live in a lang file is persisted relationally,
    | keyed by locale + key. Disable this to run the package in pure file mode
    | with no database, or point it at a dedicated connection.
    |
    */

    'metadata' => [
        'enabled' => env('TRANSLATIONS_METADATA', true),
        'connection' => env('TRANSLATIONS_DB_CONNECTION'),
    ],

];
