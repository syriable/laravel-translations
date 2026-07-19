<?php

declare(strict_types=1);

return [
    'export' => [
        'done' => 'تم تصدير :files ملفًا عبر :locales لغة في :duration.',
    ],

    'import' => [
        'done' => 'اكتمل الاستيراد.',
        'table' => [
            'locales' => 'اللغات',
            'phrases' => 'العبارات',
            'created' => 'المُنشأة',
            'updated' => 'المُحدَّثة',
            'duration' => 'المدة',
        ],
    ],

    'install' => [
        'publishing' => 'جارٍ نشر الإعدادات...',
        'migrating' => 'جارٍ تنفيذ عمليات الترحيل...',
        'importing' => 'جارٍ استيراد ملفات اللغة...',
        'imported' => 'تم استيراد :phrases عبارة عبر :locales لغة.',
        'done' => 'تم تثبيت الترجمات.',
    ],

    'prune' => [
        'dry_run' => 'سيتم حذف :count مراجعة أقدم من :days يومًا.',
        'done' => 'تم حذف :count مراجعة أقدم من :days يومًا.',
    ],

    'scan_loose' => [
        'queued' => 'تمت جدولة فحص النصوص غير المترجمة في قائمة الانتظار.',
        'done' => 'تم فحص :files ملفًا، وعُثر على :detected نصًا غير مترجم.',
    ],

    'scan_usage' => [
        'queued' => 'تمت جدولة فحص الاستخدام في قائمة الانتظار.',
        'done' => 'تم فحص :files ملفًا، وتم تسجيل :usages استخدامًا.',
    ],

    'status' => [
        'no_locales' => 'لم يتم العثور على لغات مستهدفة. شغّل translations:import أولًا.',
        'no_bundles' => 'لم يتم العثور على حِزم. شغّل translations:import أولًا.',
        'overall' => 'التغطية الإجمالية: :percent%',
        'locale_table' => [
            'locale' => 'اللغة',
            'total' => 'الإجمالي',
            'translated' => 'المترجَمة',
            'approved' => 'المعتمَدة',
            'coverage' => 'التغطية',
        ],
        'bundle_table' => [
            'bundle' => 'الحزمة',
            'phrases' => 'العبارات',
            'translated' => 'المترجَمة',
            'coverage' => 'التغطية',
        ],
    ],

    'translate' => [
        'disabled' => 'الترجمة بالذكاء الاصطناعي معطّلة. اضبط TRANSLATIONS_AI=true لتفعيلها.',
        'unknown_locale' => 'لغة غير معروفة [:code].',
        'translated_key' => 'تمت ترجمة [:key]: :value',
        'no_source' => 'لا توجد قيمة مصدر لـ [:key].',
        'queued' => 'تمت جدولة ترجمة [:code] في قائمة الانتظار.',
        'done' => 'تمت ترجمة :count رسالة إلى [:code].',
    ],

    'ai_review' => [
        'clean' => 'لم يتم العثور على مشكلات جودة في [:code].',
        'done' => 'تمت مراجعة [:code]: :high عالية، :medium متوسطة، :low منخفضة الأولوية.',
        'table' => [
            'key' => 'المفتاح',
            'severity' => 'الخطورة',
            'detail' => 'المشكلة',
            'suggestion' => 'الاقتراح',
        ],
    ],

    'validate' => [
        'queued' => 'تمت جدولة فحص الجودة في قائمة الانتظار.',
        'fixed' => 'تم إصلاح :count مشكلة تلقائيًا.',
        'missing_placeholders' => 'الترجمة تفتقد العناصر النائبة المطلوبة: :placeholders.',
        'plural_segments' => 'يجب أن تحتوي ترجمة الجمع على :expected صيغة مفصولة بخطوط عمودية (|)، تم العثور على :actual.',
        'plural_qualifiers' => 'يجب أن تحافظ ترجمة الجمع على نفس المُحدِّدات والأرقام كما في المصدر (:expected)، تم العثور على :actual.',
        'table' => [
            'checked' => 'المفحوصة',
            'errors' => 'الأخطاء',
            'warnings' => 'التحذيرات',
            'info' => 'معلومات',
        ],
    ],

    'quality' => [
        'checks' => [
            'missing_placeholder' => [
                'label' => 'عناصر نائبة مفقودة',
                'description' => 'الترجمة تفتقد العناصر النائبة: :placeholders',
            ],
            'unexpected_placeholder' => [
                'label' => 'عناصر نائبة غير متوقعة',
                'description' => 'تحتوي الترجمة على عناصر نائبة غير موجودة في المصدر: :placeholders',
            ],
            'plural' => [
                'label' => 'مُحدِّدات الجمع',
                'description' => 'مُحدِّدات الجمع لا تطابق سلسلة المصدر.',
                'suggestion' => 'حافظ على نفس مُحدِّدات الجمع (مثل {1} و [2,*]) في كل جزء.',
            ],
            'inconsistent_plural_selector' => [
                'label' => 'مُحدِّدات جمع غير متسقة',
                'description' => 'سلسلة الجمع في المصدر تفتقد مُحدِّدات صريحة (مثل {0} و [1,19] و [20,*]) في الجزء/الأجزاء: :missing.',
            ],
            'html_tag_mismatch' => [
                'label' => 'وسوم HTML',
                'description' => 'وسوم HTML لا تطابق سلسلة المصدر (المصدر: :source؛ الهدف: :target).',
            ],
            'length_ratio' => [
                'label' => 'نسبة الطول',
                'description' => 'نسبة طول الترجمة :ratio خارج النطاق المتوقع (:min–:max).',
            ],
            'whitespace' => [
                'label' => 'المسافات البيضاء',
                'description' => 'مشكلة/مشاكل في المسافات البيضاء: :problems.',
                'suggestion' => 'طابق مسافات المصدر البيضاء ووحّد المسافات المكررة.',
                'problems' => [
                    'leading_trailing' => 'مسافات بيضاء في البداية/النهاية',
                    'double_spaces' => 'مسافات مزدوجة',
                ],
            ],
            'casing' => [
                'label' => 'حالة الأحرف',
                'description' => 'حالة الحرف الأول تختلف عن سلسلة المصدر.',
                'suggestion' => 'طابق حالة أحرف المصدر.',
            ],
            'url_email' => [
                'label' => 'الروابط والبريد',
                'description' => 'تم تغيير أو حذف روابط أو عناوين بريد إلكتروني: :missing',
            ],
            'glossary' => [
                'label' => 'المسرد',
                'description' => 'لم تُطبَّق مصطلحات المسرد: :violations',
                'suggestion' => 'استخدم ترجمة المسرد المعتمدة لكل مصطلح.',
            ],
        ],
    ],

    'locale' => [
        'invalid_code' => 'رمز لغة غير صالح [:code]. يُتوقع رمز لغة مثل "en" أو "pt-BR" أو "zh-Hans".',
    ],

    'enums' => [
        'severity' => [
            'error' => 'خطأ',
            'warning' => 'تحذير',
            'info' => 'معلومات',
        ],
        'review_severity' => [
            'low' => 'منخفضة',
            'medium' => 'متوسطة',
            'high' => 'عالية',
        ],
    ],
];
