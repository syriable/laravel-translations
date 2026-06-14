<?php

use Syriable\Translations\Analytics\BundleCoverage;
use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Models\Bundle;
use Syriable\Translations\Models\Locale;

beforeEach(function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Locale::flushSourceCache();
    $this->coverage = app(BundleCoverage::class);
});

it('reports zero percent for a bundle with no phrases', function (): void {
    Translations::addLocale('es');
    Bundle::query()->create(['name' => 'empty', 'format' => 'php']);

    $row = collect($this->coverage->coverage('empty'))->firstWhere('name', 'empty');

    expect($row['total'])->toBe(0);
    expect($row['translated'])->toBe(0);
    expect($row['percent'])->toBe(0.0);
});

it('reports zero translated when there are no target locales', function (): void {
    Translations::set('cart.checkout', 'Checkout', 'en');

    $row = collect($this->coverage->coverage('cart'))->firstWhere('name', 'cart');

    expect($row['total'])->toBe(1);
    expect($row['translated'])->toBe(0);
    expect($row['percent'])->toBe(0.0);
});

it('counts a phrase as translated only when every target locale has a non-open message', function (): void {
    Translations::addLocale('es');
    Translations::addLocale('fr');

    Translations::set('cart.checkout', 'Checkout', 'en');
    Translations::set('cart.cancel', 'Cancel', 'en');

    Translations::set('cart.checkout', 'Pagar', 'es');
    Translations::set('cart.checkout', 'Payer', 'fr');

    Translations::set('cart.cancel', 'Cancelar', 'es');

    $row = collect($this->coverage->coverage('cart'))->firstWhere('name', 'cart');

    expect($row['total'])->toBe(2);
    expect($row['translated'])->toBe(1);
    expect($row['percent'])->toBe(50.0);
});

it('exposes progress through the Bundle scope and Insights dashboard', function (): void {
    Translations::addLocale('es');

    Translations::set('cart.checkout', 'Checkout', 'en');
    Translations::set('cart.checkout', 'Pagar', 'es');

    $bundle = Bundle::query()->withTranslationProgress()->where('name', 'cart')->first();
    expect($bundle->translationProgressPercent())->toBe(100.0);

    expect(Translations::insights()->bundleCoverage())->toHaveCount(1);
    expect(Translations::insights()->dashboard())->toHaveKey('bundle_coverage');
});
