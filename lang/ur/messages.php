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

    'ai_review' => [
        'clean' => '[:code] میں کوئی کوالٹی مسئلہ نہیں ملا۔',
        'done' => '[:code] کا جائزہ لیا گیا: :high زیادہ، :medium درمیانہ، :low کم ترجیحی مسائل۔',
        'table' => [
            'key' => 'کلید',
            'severity' => 'شدت',
            'detail' => 'مسئلہ',
            'suggestion' => 'تجویز',
        ],
    ],

    'validate' => [
        'queued' => 'کوالٹی اسکین قطار میں بھیج دیا گیا۔',
        'fixed' => ':count مسائل خودکار طور پر حل کیے گئے۔',
        'missing_placeholders' => 'ترجمے میں مطلوبہ پلیس ہولڈرز غائب ہیں: :placeholders۔',
        'plural_segments' => 'جمع کے ترجمے میں پائپ (|) سے الگ کیے گئے :expected متغیرات ہونے چاہئیں، :actual ملے۔',
        'plural_qualifiers' => 'جمع کے ترجمے میں ماخذ جیسے ہی سلیکٹرز اور نمبرز برقرار رہنے چاہئیں (:expected)، :actual ملے۔',
        'table' => [
            'checked' => 'جانچے گئے',
            'errors' => 'خرابیاں',
            'warnings' => 'انتباہات',
            'info' => 'معلومات',
        ],
    ],

    'quality' => [
        'checks' => [
            'missing_placeholder' => [
                'label' => 'غائب پلیس ہولڈرز',
                'description' => 'ترجمے میں پلیس ہولڈرز غائب ہیں: :placeholders',
            ],
            'unexpected_placeholder' => [
                'label' => 'غیر متوقع پلیس ہولڈرز',
                'description' => 'ترجمے میں ایسے پلیس ہولڈرز ہیں جو ماخذ میں موجود نہیں: :placeholders',
            ],
            'plural' => [
                'label' => 'جمع سلیکٹرز',
                'description' => 'جمع سلیکٹرز ماخذ سٹرنگ سے مماثل نہیں۔',
                'suggestion' => 'ہر حصے میں وہی جمع سلیکٹرز رکھیں (مثلاً {1}، [2,*])۔',
            ],
            'inconsistent_plural_selector' => [
                'label' => 'غیر متسق جمع سلیکٹرز',
                'description' => 'ماخذ جمع سٹرنگ میں واضح سلیکٹرز غائب ہیں (مثلاً {0}، [1,19]، [20,*]) ان حصوں پر: :missing۔',
            ],
            'html_tag_mismatch' => [
                'label' => 'HTML ٹیگز',
                'description' => 'HTML ٹیگز ماخذ سٹرنگ سے مماثل نہیں (ماخذ: :source؛ ہدف: :target)۔',
            ],
            'length_ratio' => [
                'label' => 'لمبائی کا تناسب',
                'description' => 'ترجمے کی لمبائی کا تناسب :ratio متوقع حد (:min–:max) سے باہر ہے۔',
            ],
            'whitespace' => [
                'label' => 'خالی جگہیں',
                'description' => 'خالی جگہ کا مسئلہ/مسائل: :problems۔',
                'suggestion' => 'ماخذ کی خالی جگہوں سے میل کھائیں اور دہرائی گئی جگہیں کم کریں۔',
                'problems' => [
                    'leading_trailing' => 'شروع/آخر کی خالی جگہیں',
                    'double_spaces' => 'دوہری جگہیں',
                ],
            ],
            'casing' => [
                'label' => 'حروف کی صورت',
                'description' => 'پہلے حرف کی صورت ماخذ سٹرنگ سے مختلف ہے۔',
                'suggestion' => 'ماخذ کی حروف کی صورت سے میل کھائیں۔',
            ],
            'url_email' => [
                'label' => 'URLs اور ای میلز',
                'description' => 'URLs یا ای میل پتے تبدیل یا حذف کیے گئے: :missing',
            ],
            'glossary' => [
                'label' => 'لغت',
                'description' => 'لغت کی اصطلاحات لاگو نہیں ہوئیں: :violations',
                'suggestion' => 'ہر اصطلاح کے لیے منظور شدہ لغت ترجمہ استعمال کریں۔',
            ],
        ],
    ],

    'locale' => [
        'invalid_code' => 'غلط زبان کوڈ [:code]۔ متوقع زبان کوڈ جیسے "en"، "pt-BR" یا "zh-Hans"۔',
    ],

    'enums' => [
        'severity' => [
            'error' => 'خرابی',
            'warning' => 'انتباہ',
            'info' => 'معلومات',
        ],
        'review_severity' => [
            'low' => 'کم',
            'medium' => 'درمیانہ',
            'high' => 'زیادہ',
        ],
    ],
];
