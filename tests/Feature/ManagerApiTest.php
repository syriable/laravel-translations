<?php

use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Message;

beforeEach(function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Locale::flushSourceCache();
});

it('sets and gets a value, creating the phrase on demand', function (): void {
    Translations::set('messages.greeting', 'Hello', 'en');

    expect(Translations::get('messages.greeting', 'en'))->toBe('Hello');
    expect(Translations::has('messages.greeting', 'en'))->toBeTrue();
    expect(Translations::has('messages.missing', 'en'))->toBeFalse();
});

it('defaults to the source locale when none is given', function (): void {
    Translations::set('messages.greeting', 'Hello');

    expect(Translations::get('messages.greeting'))->toBe('Hello');
});

it('treats keys without a dot as json bundle entries', function (): void {
    Translations::set('Welcome friend', 'Welcome friend', 'en');

    expect(Translations::get('Welcome friend', 'en'))->toBe('Welcome friend');
});

it('forgets a single locale value but keeps the phrase', function (): void {
    Translations::addLocale('es');
    Translations::set('messages.greeting', 'Hola', 'es');

    Translations::forget('messages.greeting', 'es');

    expect(Translations::get('messages.greeting', 'es'))->toBeNull();
    expect(Translations::has('messages.greeting', 'en') || Message::query()->count() > 0)->toBeTrue();
});

it('seeds messages for newly added locales', function (): void {
    Translations::set('messages.greeting', 'Hello', 'en');
    Translations::addLocale('de');

    $de = Locale::query()->where('code', 'de')->first();
    expect(Message::query()->where('locale_id', $de->id)->count())->toBeGreaterThan(0);
});
