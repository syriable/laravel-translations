<?php

declare(strict_types=1);

return [
    'export' => [
        'done' => 'Exported :files files across :locales locales in :duration.',
    ],

    'import' => [
        'done' => 'Import complete.',
        'table' => [
            'locales' => 'Locales',
            'phrases' => 'Phrases',
            'created' => 'Created',
            'updated' => 'Updated',
            'duration' => 'Duration',
        ],
    ],

    'install' => [
        'publishing' => 'Publishing configuration...',
        'migrating' => 'Running migrations...',
        'importing' => 'Importing language files...',
        'imported' => 'Imported :phrases phrases across :locales locales.',
        'done' => 'Translations installed.',
    ],

    'prune' => [
        'dry_run' => ':count revisions older than :days days would be pruned.',
        'done' => 'Pruned :count revisions older than :days days.',
    ],

    'scan_loose' => [
        'queued' => 'Hardcoded-string scan dispatched to the queue.',
        'done' => 'Scanned :files files, found :detected hardcoded strings.',
    ],

    'scan_usage' => [
        'queued' => 'Usage scan dispatched to the queue.',
        'done' => 'Scanned :files files, recorded :usages usages.',
    ],

    'status' => [
        'no_locales' => 'No target locales found. Run translations:import first.',
        'no_bundles' => 'No bundles found. Run translations:import first.',
        'overall' => 'Overall coverage: :percent%',
        'locale_table' => [
            'locale' => 'Locale',
            'total' => 'Total',
            'translated' => 'Translated',
            'approved' => 'Approved',
            'coverage' => 'Coverage',
        ],
        'bundle_table' => [
            'bundle' => 'Bundle',
            'phrases' => 'Phrases',
            'translated' => 'Translated',
            'coverage' => 'Coverage',
        ],
    ],

    'translate' => [
        'disabled' => 'AI translation is disabled. Set TRANSLATIONS_AI=true to enable it.',
        'unknown_locale' => 'Unknown locale [:code].',
        'translated_key' => 'Translated [:key]: :value',
        'no_source' => 'No source value for [:key].',
        'queued' => 'Translation of [:code] dispatched to the queue.',
        'done' => 'Translated :count messages into [:code].',
    ],

    'validate' => [
        'queued' => 'Quality scan dispatched to the queue.',
        'fixed' => 'Auto-fixed :count issues.',
        'missing_placeholders' => 'The translation is missing required placeholders: :placeholders.',
        'table' => [
            'checked' => 'Checked',
            'errors' => 'Errors',
            'warnings' => 'Warnings',
            'info' => 'Info',
        ],
    ],

    'locale' => [
        'invalid_code' => 'Invalid locale code [:code]. Expected a language code like "en", "pt-BR" or "zh-Hans".',
    ],
];
