<?php

declare(strict_types=1);

use Syriable\Metrics\Facades\Metrics;
use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Message;

beforeEach(function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();
});

it('computes overall translation quality without sql errors', function (): void {
    Translations::set('messages.welcome', 'Hello :name', 'en');
    Translations::set('messages.welcome', 'Hola :name', 'es', ['status' => 'approved']);
    Translations::set('messages.goodbye', 'Goodbye', 'en');
    Translations::set('messages.goodbye', 'Adiós', 'es');

    expect(Message::query()->whereHas('locale', fn ($q) => $q->where('code', 'es'))->count())->toBe(2);

    $result = Metrics::run('translations.quality');

    expect($result->dataset('quality'))->not->toBeNull()
        ->and($result->value('translated'))->toBe(2)
        ->and($result->value('issues'))->toBe(0)
        ->and($result->value('quality'))->toEqual(70);
});
