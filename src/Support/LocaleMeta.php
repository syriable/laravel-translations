<?php

namespace Syriable\Translations\Support;

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

    public static function for(string $code): array
    {
        $base = strtolower(explode('_', str_replace('-', '_', $code))[0]);
        [$name, $native] = self::NAMES[$base] ?? [ucfirst($code), ucfirst($code)];

        return [
            'name' => $name,
            'native_name' => $native,
            'rtl' => in_array($base, self::RTL, true),
        ];
    }
}
