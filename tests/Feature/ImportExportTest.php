<?php

use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Models\Bundle;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Models\Phrase;

it('imports php, json and vendor lang files into the database', function (): void {
    makeLangFiles([
        'en/auth.php' => ['failed' => 'These credentials do not match :attribute.', 'nested' => ['key' => 'Value']],
        'es/auth.php' => ['failed' => 'Estas credenciales no coinciden :attribute.'],
        'en.json' => ['Welcome back' => 'Welcome back'],
        'vendor/package/en/messages.php' => ['hello' => 'Hello'],
    ]);

    $summary = Translations::import();

    expect(Locale::query()->pluck('code')->all())->toContain('en', 'es');
    expect(Locale::source()->code)->toBe('en');
    expect(Phrase::query()->where('key', 'nested.key')->exists())->toBeTrue();
    expect(Bundle::query()->where('namespace', 'package')->exists())->toBeTrue();
    expect($summary->localeCount)->toBeGreaterThanOrEqual(2);

    expect(Translations::get('auth.failed', 'es'))->toBe('Estas credenciales no coinciden :attribute.');
    expect(Translations::get('Welcome back', 'en'))->toBe('Welcome back');
});

it('detects placeholders and seeds missing target messages as open', function (): void {
    makeLangFiles([
        'en/auth.php' => ['failed' => 'No match for :attribute and {id}.'],
        'es/auth.php' => [],
    ]);

    Translations::import();

    $phrase = Phrase::query()->where('key', 'failed')->first();
    expect($phrase->placeholders)->toEqualCanonicalizing([':attribute', '{id}']);

    $es = Locale::query()->where('code', 'es')->first();
    $message = Message::query()->where('phrase_id', $phrase->id)->where('locale_id', $es->id)->first();
    expect($message->value)->toBeNull();
    expect($message->status->value)->toBe('open');
});

it('exports the database back to lang files', function (): void {
    makeLangFiles(['en/auth.php' => ['failed' => 'Failed']]);
    Translations::import();

    Translations::set('auth.failed', 'Echec', 'fr');
    Translations::export(['locale' => 'fr']);

    expect(require config('translations.lang_path').'/fr/auth.php')->toBe(['failed' => 'Echec']);
});

it('respects the no-overwrite option', function (): void {
    makeLangFiles(['en/auth.php' => ['failed' => 'Original']]);
    Translations::import();

    makeLangFiles(['en/auth.php' => ['failed' => 'Changed']]);
    Translations::import(['overwrite' => false]);

    expect(Translations::get('auth.failed', 'en'))->toBe('Original');
});

it('imports nested php files using slash-path bundle names and round-trips on export', function (): void {
    makeLangFiles([
        'en/auth.php' => ['failed' => 'Failed'],
        'en/filament/resources/bundle-resource.php' => ['title' => 'Bundles'],
        'en/modules/users/buyer-resource.php' => ['title' => 'Buyers'],
    ]);

    Translations::import();

    $bundle = Bundle::query()->where('name', 'filament/resources/bundle-resource')->first();
    expect($bundle)->not->toBeNull();
    expect($bundle->file_path)->toBe('filament/resources/bundle-resource.php');
    expect(Phrase::query()->whereBelongsTo($bundle)->where('key', 'title')->exists())->toBeTrue();

    expect(Translations::get('filament/resources/bundle-resource.title', 'en'))->toBe('Bundles');
    expect(Translations::get('modules/users/buyer-resource.title', 'en'))->toBe('Buyers');

    Translations::set('filament/resources/bundle-resource.title', 'Paquetes', 'es');
    Translations::export(['locale' => 'es']);

    $path = config('translations.lang_path').'/es/filament/resources/bundle-resource.php';
    expect(file_exists($path))->toBeTrue();
    expect(require $path)->toBe(['title' => 'Paquetes']);
});

it('does not collide nested files that share a filename', function (): void {
    makeLangFiles([
        'en/admin/users.php' => ['heading' => 'Admin users'],
        'en/reports/users.php' => ['heading' => 'Report users'],
    ]);

    Translations::import();

    expect(Bundle::query()->whereIn('name', ['admin/users', 'reports/users'])->count())->toBe(2);
    expect(Translations::get('admin/users.heading', 'en'))->toBe('Admin users');
    expect(Translations::get('reports/users.heading', 'en'))->toBe('Report users');
});
