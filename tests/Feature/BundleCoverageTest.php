<?php

use Syriable\Translations\Analytics\BundleCoverage;
use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Models\Bundle;
use Syriable\Translations\Models\Locale;

beforeEach(function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Locale::flushSourceCache();
});

it('reports zero progress when a bundle has no phrases', function (): void {
    Translations::addLocale('fr');

    expect(app(BundleCoverage::class)->coverage())->toBeEmpty();
});

it('counts a phrase as translated only when all target locales are done', function (): void {
    Translations::addLocale('fr');
    Translations::addLocale('de');

    Translations::set('auth.failed', 'Failed', 'en');
    Translations::set('auth.throttle', 'Throttle', 'en');

    Translations::set('auth.failed', 'Échec', 'fr');
    Translations::set('auth.failed', 'Fehlgeschlagen', 'de');

    $row = collect(app(BundleCoverage::class)->coverage('auth'))->first();

    expect($row)->toMatchArray([
        'name' => 'auth',
        'total' => 2,
        'translated' => 1,
        'percent' => 50.0,
    ]);
});

it('reports full bundle progress when every phrase is translated in all targets', function (): void {
    Translations::addLocale('fr');

    Translations::set('auth.failed', 'Failed', 'en');
    Translations::set('auth.failed', 'Échec', 'fr');

    $row = collect(Translations::insights()->bundleCoverage('auth'))->first();

    expect($row['translated'])->toBe(1);
    expect($row['total'])->toBe(1);
    expect($row['percent'])->toBe(100.0);
});

it('applies progress counts on the bundle query scope', function (): void {
    Translations::addLocale('fr');

    Translations::set('auth.failed', 'Failed', 'en');
    Translations::set('auth.throttle', 'Throttle', 'en');
    Translations::set('auth.failed', 'Échec', 'fr');

    $bundle = Bundle::query()
        ->withTranslationProgress()
        ->where('name', 'auth')
        ->first();

    expect($bundle->phrases_count)->toBe(2);
    expect($bundle->translated_phrases_count)->toBe(1);
    expect($bundle->translationProgressPercent())->toBe(50.0);
});
