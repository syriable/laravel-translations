<?php

namespace Syriable\Translations\Support;

use Syriable\Translations\Enums\Direction;

class LocaleMeta
{
    private const RTL = ['ar', 'fa', 'he', 'ur', 'ps', 'sd', 'ug', 'yi'];

    private const NAMES = [
        'en' => ['English', 'English'],
        'ar' => ['Arabic', 'العربية'],
        'es' => ['Spanish', 'Español'],
        'fr' => ['French', 'Français'],
        'de' => ['German', 'Deutsch'],
        'it' => ['Italian', 'Italiano'],
        'pt' => ['Portuguese', 'Português'],
        'nl' => ['Dutch', 'Nederlands'],
        'ru' => ['Russian', 'Русский'],
        'tr' => ['Turkish', 'Türkçe'],
        'fa' => ['Persian', 'فارسی'],
        'he' => ['Hebrew', 'עברית'],
        'zh' => ['Chinese', '中文'],
        'ja' => ['Japanese', '日本語'],
        'ko' => ['Korean', '한국어'],
        'hi' => ['Hindi', 'हिन्दी'],
        'ur' => ['Urdu', 'اردو'],
        'id' => ['Indonesian', 'Bahasa Indonesia'],
        'pl' => ['Polish', 'Polski'],
        'uk' => ['Ukrainian', 'Українська'],
    ];

    /**
     * Determine whether a locale code is well-formed.
     *
     * The code must look like a BCP 47 tag: a 2-3 letter primary subtag,
     * optionally followed by one or more subtags separated by "-" or "_"
     * (e.g. "en", "pt-BR", "zh-Hans", "xx-custom"). This rejects nonsense
     * input such as "sdfsdgv".
     */
    public static function isValidCode(string $code): bool
    {
        return (bool) preg_match('/^[a-z]{2,3}([_-][a-z0-9]{1,8})*$/i', $code);
    }

    public static function for(string $code): array
    {
        $base = strtolower(explode('_', str_replace('-', '_', $code))[0]);
        [$name, $native] = self::NAMES[$base] ?? [ucfirst($code), ucfirst($code)];

        return [
            'name' => $name,
            'native_name' => $native,
            'direction' => in_array($base, self::RTL, true) ? Direction::Rtl : Direction::Ltr,
            'code' => $code,
        ];
    }

    public static function all(): array
    {
        $locales = [];
        foreach (self::NAMES as $code => $name) {
            $locales[$code] = self::for($code);
        }
        asort($locales);

        return array_map(fn ($locale) => $locale['name'], $locales);
    }

    public static function getNameByCode(string $code): string
    {
        return self::for($code)['name'];
    }

    public static function getNativeNameByCode(string $code): string
    {
        return self::for($code)['native_name'];
    }

    public static function getDirectionByCode(string $code): string
    {
        return self::for($code)['direction'];
    }
}
