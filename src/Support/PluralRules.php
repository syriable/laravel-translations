<?php

declare(strict_types=1);

namespace Syriable\Translations\Support;

/**
 * Built-in lookup of how many plural forms a language uses.
 *
 * The counts follow the CLDR plural categories that Laravel's own choice
 * resolution relies on (e.g. English has 2 forms, Polish 3, Arabic 6). Only
 * languages with a well-established count are listed; an unknown language
 * returns null so callers can choose to skip rather than guess.
 */
final class PluralRules
{
    /**
     * Base language code => number of plural forms.
     *
     * @var array<string, int>
     */
    private const FORMS = [
        // Languages without plural inflection.
        'ja' => 1, 'ko' => 1, 'zh' => 1, 'vi' => 1, 'th' => 1, 'id' => 1, 'ms' => 1,

        // Two forms (singular / plural).
        'en' => 2, 'de' => 2, 'nl' => 2, 'sv' => 2, 'da' => 2, 'no' => 2, 'nb' => 2,
        'nn' => 2, 'es' => 2, 'it' => 2, 'pt' => 2, 'ca' => 2, 'eu' => 2, 'fi' => 2,
        'et' => 2, 'el' => 2, 'hu' => 2, 'tr' => 2, 'fr' => 2,

        // Three forms.
        'ru' => 3, 'uk' => 3, 'be' => 3, 'pl' => 3, 'cs' => 3, 'sk' => 3, 'hr' => 3,
        'sr' => 3, 'bs' => 3, 'ro' => 3, 'lt' => 3,

        // Four or more forms.
        'sl' => 4,
        'ga' => 5,
        'ar' => 6,
    ];

    /**
     * The number of plural forms for a locale, or null when the language is
     * not known. Region subtags are ignored ("pt_BR" resolves as "pt").
     */
    public static function formCount(string $locale): ?int
    {
        $base = self::baseLanguage($locale);

        return self::FORMS[$base] ?? null;
    }

    private static function baseLanguage(string $locale): string
    {
        $normalized = strtolower(str_replace('-', '_', trim($locale)));

        return explode('_', $normalized, 2)[0];
    }
}
