<?php

use Syriable\Translations\Enums\Direction;
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

it('allows custom locale attributes to override locale meta defaults', function (): void {
    $locale = Translations::addLocale('xx-custom', [
        'name' => 'Custom Locale',
        'native_name' => 'Locale personnalisée',
        'direction' => Direction::Rtl,
    ]);

    expect($locale->code)->toBe('xx-custom')
        ->and($locale->name)->toBe('Custom Locale')
        ->and($locale->native_name)->toBe('Locale personnalisée')
        ->and($locale->direction)->toBe(Direction::Rtl);
});

it('rejects nonsense locale codes', function (): void {
    Translations::addLocale('sdfsdgv');
})->throws(InvalidArgumentException::class);

it('does not persist a locale when the code is invalid', function (): void {
    try {
        Translations::addLocale('sdfsdgv');
    } catch (InvalidArgumentException) {
        // expected
    }

    expect(Locale::query()->where('code', 'sdfsdgv')->exists())->toBeFalse();
});

it('accepts well-formed locale codes', function (string $code): void {
    expect(Translations::addLocale($code)->code)->toBe($code);
})->with(['es', 'fil', 'pt-BR', 'zh-Hans', 'en_US', 'xx-custom']);
