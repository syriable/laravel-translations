<?php

declare(strict_types=1);

use Syriable\Translations\Domain\Enums\HardcodedStatus;
use Syriable\Translations\Models\HardcodedIgnore;
use Syriable\Translations\Models\HardcodedString;

beforeEach(function () {
    config()->set('translations.extraction.paths', [fixturePath('hardcoded')]);
});

it('detects literal text and translatable attributes', function () {
    $this->artisan('translations:detect-hardcoded')->assertSuccessful();

    $texts = HardcodedString::query()->pluck('text')->all();

    expect($texts)->toContain('Welcome to our site')
        ->and($texts)->toContain('Enter your name')
        ->and($texts)->toContain('Already translated:');
});

it('ignores text already wrapped in a translation helper or comment', function () {
    $this->artisan('translations:detect-hardcoded')->assertSuccessful();

    $texts = HardcodedString::query()->pluck('text')->all();

    expect($texts)->not->toContain('messages.intro')
        ->and($texts)->not->toContain('buttons.save')
        ->and($texts)->not->toContain('Hidden comment text')
        ->and($texts)->not->toContain('a code string');
});

it('records the element type and scanner', function () {
    $this->artisan('translations:detect-hardcoded')->assertSuccessful();

    $placeholder = HardcodedString::query()->where('text', 'Enter your name')->sole();

    expect($placeholder->element_type)->toBe('placeholder')
        ->and($placeholder->scanner_type)->toBe('blade')
        ->and($placeholder->status)->toBe(HardcodedStatus::Pending);
});

it('skips strings that have been ignored', function () {
    HardcodedIgnore::query()->create(['text_hash' => sha1('Welcome to our site')]);

    $this->artisan('translations:detect-hardcoded')->assertSuccessful();

    expect(HardcodedString::query()->where('text', 'Welcome to our site')->exists())->toBeFalse();
});

it('preserves converted entries across a re-scan', function () {
    $this->artisan('translations:detect-hardcoded')->assertSuccessful();

    $row = HardcodedString::query()->where('text', 'Welcome to our site')->sole();
    $row->update(['status' => HardcodedStatus::Converted, 'converted_key' => 'messages.welcome']);

    $this->artisan('translations:detect-hardcoded')->assertSuccessful();

    $reloaded = HardcodedString::query()->where('text', 'Welcome to our site')->sole();

    expect($reloaded->status)->toBe(HardcodedStatus::Converted)
        ->and($reloaded->converted_key)->toBe('messages.welcome')
        ->and(HardcodedString::query()->where('text', 'Welcome to our site')->count())->toBe(1);
});

it('does not record when metadata is disabled', function () {
    config()->set('translations.metadata.enabled', false);

    $this->artisan('translations:detect-hardcoded')->assertSuccessful();

    expect(HardcodedString::query()->count())->toBe(0);
});
