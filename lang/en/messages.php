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

    'ai_review' => [
        'clean' => 'No quality issues found in [:code].',
        'done' => 'Reviewed [:code]: :high high, :medium medium, :low low-priority issues.',
        'table' => [
            'key' => 'Key',
            'severity' => 'Severity',
            'detail' => 'Issue',
            'suggestion' => 'Suggestion',
        ],
    ],

    'validate' => [
        'queued' => 'Quality scan dispatched to the queue.',
        'fixed' => 'Auto-fixed :count issues.',
        'missing_placeholders' => 'The translation is missing required placeholders: :placeholders.',
        'plural_segments' => 'The plural translation must have :expected variants separated by pipes (|), got :actual.',
        'plural_qualifiers' => 'The plural translation must keep the same selectors and numbers as the source (:expected), got :actual.',
        'table' => [
            'checked' => 'Checked',
            'errors' => 'Errors',
            'warnings' => 'Warnings',
            'info' => 'Info',
        ],
    ],

    'quality' => [
        'checks' => [
            'missing_placeholder' => [
                'label' => 'Missing placeholders',
                'description' => 'Translation is missing placeholders: :placeholders',
            ],
            'unexpected_placeholder' => [
                'label' => 'Unexpected placeholders',
                'description' => 'Translation has placeholders not present in the source: :placeholders',
            ],
            'plural' => [
                'label' => 'Plural selectors',
                'description' => 'Plural selectors do not match the source string.',
                'suggestion' => 'Keep the same plural selectors (e.g. {1}, [2,*]) in each segment.',
            ],
            'inconsistent_plural_selector' => [
                'label' => 'Inconsistent plural selectors',
                'description' => 'The source plural string is missing explicit selectors (e.g. {0}, [1,19], [20,*]) on segment(s): :missing.',
            ],
            'html_tag_mismatch' => [
                'label' => 'HTML tags',
                'description' => 'HTML tags do not match the source string (source: :source; target: :target).',
            ],
            'length_ratio' => [
                'label' => 'Length ratio',
                'description' => 'Translation length ratio :ratio is outside the expected range (:min–:max).',
            ],
            'whitespace' => [
                'label' => 'Whitespace',
                'description' => 'Whitespace issue(s): :problems.',
                'suggestion' => 'Match the source whitespace and collapse repeated spaces.',
                'problems' => [
                    'leading_trailing' => 'leading/trailing whitespace',
                    'double_spaces' => 'double spaces',
                ],
            ],
            'casing' => [
                'label' => 'Capitalization',
                'description' => 'The first letter capitalization differs from the source string.',
                'suggestion' => 'Match the source capitalization.',
            ],
            'url_email' => [
                'label' => 'URLs and emails',
                'description' => 'URLs or email addresses were altered or dropped: :missing',
            ],
            'glossary' => [
                'label' => 'Glossary',
                'description' => 'Glossary terms were not applied: :violations',
                'suggestion' => 'Use the approved glossary translation for each term.',
            ],
        ],
    ],

    'locale' => [
        'invalid_code' => 'Invalid locale code [:code]. Expected a language code like "en", "pt-BR" or "zh-Hans".',
    ],

    'enums' => [
        'severity' => [
            'error' => 'Error',
            'warning' => 'Warning',
            'info' => 'Info',
        ],
        'review_severity' => [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
        ],
    ],
];
