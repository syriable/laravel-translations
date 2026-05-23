<?php

declare(strict_types=1);

use Syriable\Translations\Models\KeyContext;

beforeEach(function () {
    config()->set('translations.extraction.paths', [fixturePath('source')]);
});

it('records the source locations of used keys', function () {
    $this->artisan('translations:scan-context')->assertSuccessful();

    $welcome = KeyContext::query()->forKey('messages.welcome')->get();

    // messages.welcome is used in both Welcome.php and welcome.blade.php.
    expect($welcome)->toHaveCount(2)
        ->and($welcome->pluck('file_type')->sort()->values()->all())->toBe(['blade', 'php'])
        ->and($welcome->every(fn (KeyContext $c): bool => $c->line_number > 0))->toBeTrue();
});

it('captures the helper used at each call site', function () {
    $this->artisan('translations:scan-context')->assertSuccessful();

    $apples = KeyContext::query()->forKey('messages.apples')->get();

    expect($apples->pluck('helper')->unique()->all())->toBe(['trans_choice']);
});

it('replaces previous contexts on a re-scan', function () {
    $this->artisan('translations:scan-context')->assertSuccessful();
    $first = KeyContext::query()->count();

    $this->artisan('translations:scan-context')->assertSuccessful();

    expect(KeyContext::query()->count())->toBe($first);
});

it('does not record contexts when metadata is disabled', function () {
    config()->set('translations.metadata.enabled', false);

    $this->artisan('translations:scan-context')->assertSuccessful();

    expect(KeyContext::query()->count())->toBe(0);
});
