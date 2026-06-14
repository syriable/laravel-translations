<?php

use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Phrase;
use Syriable\Translations\Models\Revision;

it('installs and imports via the install command', function (): void {
    makeLangFiles(['en/auth.php' => ['failed' => 'Failed']]);

    $this->artisan('translations:install', ['--import' => true])->assertSuccessful();

    expect(Phrase::query()->where('key', 'failed')->exists())->toBeTrue();
});

it('imports from disk via the import command', function (): void {
    makeLangFiles([
        'en/auth.php' => ['failed' => 'Failed'],
        'es/auth.php' => ['failed' => 'Fallo'],
    ]);

    $this->artisan('translations:import')->assertSuccessful();

    expect(Translations::get('auth.failed', 'es'))->toBe('Fallo');
});

it('keeps existing values with import --no-overwrite', function (): void {
    makeLangFiles(['en/auth.php' => ['failed' => 'Original']]);
    $this->artisan('translations:import')->assertSuccessful();

    makeLangFiles(['en/auth.php' => ['failed' => 'Changed']]);
    $this->artisan('translations:import', ['--no-overwrite' => true])->assertSuccessful();

    expect(Translations::get('auth.failed', 'en'))->toBe('Original');
});

it('exports to disk via the export command', function (): void {
    makeLangFiles(['en/auth.php' => ['failed' => 'Failed']]);
    Translations::import();
    Translations::set('auth.failed', 'Fallo', 'es');

    $this->artisan('translations:export', ['--locale' => 'es'])->assertSuccessful();

    expect(require config('translations.lang_path').'/es/auth.php')->toBe(['failed' => 'Fallo']);
});

it('reports locale and bundle progress via the status command', function (): void {
    makeLangFiles([
        'en/auth.php' => ['failed' => 'Failed'],
        'es/auth.php' => [],
    ]);
    Translations::import();

    $this->artisan('translations:status')->assertSuccessful();
    $this->artisan('translations:status', ['--bundles' => true])->assertSuccessful();
});

it('validates and auto-fixes via the validate command', function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();
    config()->set('translations.quality.run_on_save', false);

    Translations::set('m.label', 'Save changes', 'en');
    Translations::set('m.label', '  save changes  ', 'es');

    $this->artisan('translations:validate', ['--fix' => true])->assertSuccessful();

    expect(Translations::get('m.label', 'es'))->toBe('Save changes');
});

it('refuses AI translation when disabled', function (): void {
    Translations::addLocale('es');

    $this->artisan('translations:translate', ['locale' => 'es'])->assertFailed();
});

it('prunes old revisions, honouring --dry-run', function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();

    Translations::set('m.one', 'One', 'es');
    Translations::set('m.one', 'Uno', 'es');
    Revision::query()->oldest('id')->first()->update(['created_at' => now()->subDays(200)]);

    $before = Revision::query()->count();

    $this->artisan('translations:prune-revisions', ['--dry-run' => true])->assertSuccessful();
    expect(Revision::query()->count())->toBe($before);

    $this->artisan('translations:prune-revisions')->assertSuccessful();
    expect(Revision::query()->count())->toBeLessThan($before);
});
