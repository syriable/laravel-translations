<?php

use Syriable\Translations\Enums\RevisionReason;
use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Models\QualityIssue;
use Syriable\Translations\Models\Revision;

it('does not record per-row revisions or quality issues for bulk imports', function (): void {
    makeLangFiles([
        'en/auth.php' => ['failed' => 'Hello :name'],
        'es/auth.php' => ['failed' => 'Hola'],
    ]);

    Translations::import();

    expect(Revision::query()->count())->toBe(0);
    expect(QualityIssue::query()->count())->toBe(0);
    expect(Translations::get('auth.failed', 'es'))->toBe('Hola');
});

it('still records revisions and quality issues for individual writes', function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();

    Translations::set('auth.failed', 'Hello :name', 'en');
    Translations::set('auth.failed', 'Hola', 'es');

    expect(Revision::query()->count())->toBeGreaterThan(0);
    expect(QualityIssue::query()->where('check', 'missing_placeholder')->exists())->toBeTrue();
});

it('rolls back a failed fresh import instead of leaving the catalog empty', function (): void {
    makeLangFiles(['en/auth.php' => ['failed' => 'Failed']]);
    Translations::import();
    expect(Translations::get('auth.failed', 'en'))->toBe('Failed');

    file_put_contents(config('translations.lang_path').'/en/broken.php', "<?php\n\nthrow new RuntimeException('boom');\n");

    expect(fn () => Translations::import(['fresh' => true]))->toThrow(RuntimeException::class);

    expect(Translations::get('auth.failed', 'en'))->toBe('Failed');
});

it('clears the revision stamp even when the wrapped save throws', function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Locale::flushSourceCache();

    $message = Translations::set('auth.failed', 'Failed', 'en');
    Revision::query()->delete();

    try {
        Message::withStamp(RevisionReason::Bulk->value, 'attacker', ['leak' => true], function (): void {
            throw new RuntimeException('boom');
        });
    } catch (Throwable) {
    }

    $message->update(['value' => 'Changed']);

    $revision = Revision::query()->latest('id')->first();
    expect($revision->reason)->toBe(RevisionReason::Manual);
    expect($revision->changed_by)->toBeNull();
    expect($revision->meta)->toBe([]);
});

it('invalidates the analytics cache when a translation changes', function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();

    Translations::set('a.one', 'One', 'en');
    $before = Translations::insights()->dashboard();
    expect(collect($before['coverage'])->firstWhere('locale', 'es')['translated'])->toBe(0);

    Translations::set('a.one', 'Uno', 'es');
    $after = Translations::insights()->dashboard();
    expect(collect($after['coverage'])->firstWhere('locale', 'es')['translated'])->toBe(1);
});
