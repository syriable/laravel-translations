<?php

use Syriable\Translations\Ai\FakeTranslator;
use Syriable\Translations\Contracts\Translator;
use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Models\AiUsage;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Models\Phrase;

beforeEach(function (): void {
    $this->fake = new FakeTranslator(fn ($request) => 'TR:'.$request->text);
    $this->app->instance(Translator::class, $this->fake);

    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();
});

it('applies an AI translation to a single phrase and logs usage', function (): void {
    Translations::set('messages.greeting', 'Hello there', 'en');

    $message = Translations::translate('messages.greeting', 'es');

    expect($message->value)->toBe('TR:Hello there');
    expect($message->ai_generated)->toBeTrue();
    expect(AiUsage::query()->where('success', true)->count())->toBe(1);
});

it('forwards glossary terms and context into the request', function (): void {
    Translations::set('messages.cart', 'Open your cart', 'en');

    $term = Translations::glossary()->define('cart');
    $es = Locale::query()->where('code', 'es')->first();
    Translations::glossary()->translate($term, $es->id, 'carrito');

    Translations::translate('messages.cart', 'es');

    expect($this->fake->requests[0]->glossary)->toBe(['cart' => 'carrito']);
});

it('exposes an explanatory note on the suggestion result', function (): void {
    Translations::set('messages.greeting', 'Hello there', 'en');

    $phrase = Phrase::query()->where('key', 'greeting')->firstOrFail();
    $es = Locale::query()->where('code', 'es')->first();

    $result = Translations::ai()->suggest($phrase, $es);

    expect($result->note())->toBe('Fake explanation in en.');
    expect($result->recommended()['recommended'])->toBeTrue();
});

it('translates every open message for a locale', function (): void {
    Translations::set('messages.a', 'Alpha one', 'en');
    Translations::set('messages.b', 'Beta two', 'en');

    $es = Locale::query()->where('code', 'es')->first();
    $count = Translations::ai()->translateOpen($es);

    expect($count)->toBe(2);
    expect(Message::query()->where('locale_id', $es->id)->open()->count())->toBe(0);
});
