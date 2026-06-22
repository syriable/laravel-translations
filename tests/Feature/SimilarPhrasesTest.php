<?php

use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Phrase;

beforeEach(function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Locale::flushSourceCache();

    foreach (['accepted', 'accepted_if', 'accepted_unless', 'active_url', 'after'] as $key) {
        Translations::set("validation.{$key}", $key, 'en');
    }
});

it('surfaces keys sharing the leading segment in the same bundle', function (): void {
    $keys = Translations::similar('validation.accepted')->map->key->all();

    expect($keys)->toEqualCanonicalizing(['accepted_if', 'accepted_unless']);
});

it('is symmetric — a derived key surfaces its base key', function (): void {
    $keys = Translations::similar('validation.accepted_if')->map->key->all();

    expect($keys)->toEqualCanonicalizing(['accepted', 'accepted_unless']);
});

it('excludes the given phrase by default and can include it', function (): void {
    $without = Translations::similar('validation.accepted')->map->key->all();
    $with = Translations::similar('validation.accepted', ['include_self' => true])->map->key->all();

    expect($without)->not->toContain('accepted')
        ->and($with)->toContain('accepted');
});

it('does not cross bundle boundaries', function (): void {
    Translations::set('auth.accepted_terms', 'x', 'en');

    $keys = Translations::similar('validation.accepted')->map->dottedKey()->all();

    expect($keys)->not->toContain('auth.accepted_terms');
});

it('respects a custom segment depth', function (): void {
    foreach (['custom.name.required', 'custom.name.max', 'custom.email.required'] as $key) {
        Translations::set("validation.{$key}", $key, 'en');
    }

    $depthOne = Translations::similar('validation.custom.name.required')->map->key->all();
    $depthTwo = Translations::similar('validation.custom.name.required', ['segments' => 2])->map->key->all();

    expect($depthOne)->toContain('custom.name.max', 'custom.email.required')
        ->and($depthTwo)->toContain('custom.name.max')
        ->and($depthTwo)->not->toContain('custom.email.required');
});

it('caps results with the limit option', function (): void {
    expect(Translations::similar('validation.accepted', ['limit' => 1]))->toHaveCount(1);
});

it('returns an empty collection for an unknown key', function (): void {
    expect(Translations::similar('validation.nonexistent'))->toBeEmpty();
});

it('splits keys into prefix segments', function (): void {
    expect(Phrase::segments('accepted_if'))->toBe(['accepted', 'if'])
        ->and(Phrase::segments('custom.name.required'))->toBe(['custom', 'name', 'required'])
        ->and(Phrase::segments('accepted'))->toBe(['accepted']);
});
