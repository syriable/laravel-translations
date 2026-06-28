<?php

declare(strict_types=1);

return [
    'export' => [
        'done' => ':files فائلیں :locales زبانوں میں :duration میں برآمد کی گئیں۔',
    ],

    'import' => [
        'done' => 'درآمد مکمل ہوگئی۔',
        'table' => [
            'locales' => 'زبانیں',
            'phrases' => 'فقرے',
            'created' => 'تخلیق شدہ',
            'updated' => 'اپ ڈیٹ شدہ',
            'duration' => 'دورانیہ',
        ],
    ],

    'install' => [
        'publishing' => 'ترتیبات شائع کی جا رہی ہیں...',
        'migrating' => 'مائیگریشنز چلائی جا رہی ہیں...',
        'importing' => 'زبان کی فائلیں درآمد کی جا رہی ہیں...',
        'imported' => ':phrases فقرے :locales زبانوں میں درآمد کیے گئے۔',
        'done' => 'ترجمے انسٹال ہوگئے۔',
    ],

    'prune' => [
        'dry_run' => ':days دن سے پرانی :count نظرثانیاں حذف کی جائیں گی۔',
        'done' => ':days دن سے پرانی :count نظرثانیاں حذف کر دی گئیں۔',
    ],

    'scan_loose' => [
        'queued' => 'ہارڈ کوڈڈ سٹرنگ اسکین قطار میں بھیج دیا گیا۔',
        'done' => ':files فائلیں اسکین کی گئیں، :detected ہارڈ کوڈڈ سٹرنگز ملیں۔',
    ],

    'scan_usage' => [
        'queued' => 'استعمال اسکین قطار میں بھیج دیا گیا۔',
        'done' => ':files فائلیں اسکین کی گئیں، :usages استعمالات ریکارڈ کیے گئے۔',
    ],

    'status' => [
        'no_locales' => 'کوئی ہدف زبان نہیں ملی۔ پہلے translations:import چلائیں۔',
        'no_bundles' => 'کوئی بنڈل نہیں ملا۔ پہلے translations:import چلائیں۔',
        'overall' => 'مجموعی کوریج: :percent%',
        'locale_table' => [
            'locale' => 'زبان',
            'total' => 'کل',
            'translated' => 'ترجمہ شدہ',
            'approved' => 'منظور شدہ',
            'coverage' => 'کوریج',
        ],
        'bundle_table' => [
            'bundle' => 'بنڈل',
            'phrases' => 'فقرے',
            'translated' => 'ترجمہ شدہ',
            'coverage' => 'کوریج',
        ],
    ],

    'translate' => [
        'disabled' => 'AI ترجمہ غیر فعال ہے۔ اسے فعال کرنے کے لیے TRANSLATIONS_AI=true سیٹ کریں۔',
        'unknown_locale' => 'نامعلوم زبان [:code]۔',
        'translated_key' => '[:key] کا ترجمہ ہوا: :value',
        'no_source' => '[:key] کے لیے کوئی ماخذ قدر نہیں۔',
        'queued' => '[:code] کا ترجمہ قطار میں بھیج دیا گیا۔',
        'done' => ':count پیغامات کا [:code] میں ترجمہ ہوگیا۔',
    ],

    'validate' => [
        'queued' => 'کوالٹی اسکین قطار میں بھیج دیا گیا۔',
        'fixed' => ':count مسائل خودکار طور پر حل کیے گئے۔',
        'missing_placeholders' => 'ترجمے میں مطلوبہ پلیس ہولڈرز غائب ہیں: :placeholders۔',
        'table' => [
            'checked' => 'جانچے گئے',
            'errors' => 'خرابیاں',
            'warnings' => 'انتباہات',
            'info' => 'معلومات',
        ],
    ],

    'locale' => [
        'invalid_code' => 'غلط زبان کوڈ [:code]۔ متوقع زبان کوڈ جیسے "en"، "pt-BR" یا "zh-Hans"۔',
    ],
];
