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

    'validate' => [
        'queued' => 'تمت جدولة فحص الجودة في قائمة الانتظار.',
        'fixed' => 'تم إصلاح :count مشكلة تلقائيًا.',
        'missing_placeholders' => 'الترجمة تفتقد العناصر النائبة المطلوبة: :placeholders.',
        'table' => [
            'checked' => 'المفحوصة',
            'errors' => 'الأخطاء',
            'warnings' => 'التحذيرات',
            'info' => 'معلومات',
        ],
    ],

    'locale' => [
        'invalid_code' => 'رمز لغة غير صالح [:code]. يُتوقع رمز لغة مثل "en" أو "pt-BR" أو "zh-Hans".',
    ],
];
