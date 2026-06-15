<?php

use Syriable\Translations\Ai\FakeTranslator;
use Syriable\Translations\Contracts\Translator;
use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Glossary\Glossary;
use Syriable\Translations\Models\Locale;

beforeEach(function (): void {
    $this->fake = new FakeTranslator(fn ($request) => 'TR:'.$request->text);
    $this->app->instance(Translator::class, $this->fake);
    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();
});

it('forwards an allowlisted provider to the translator', function (): void {
    config()->set('translations.ai.allowed_providers', ['openai']);
    Translations::set('messages.x', 'Hello there', 'en');

    Translations::translate('messages.x', 'es', ['provider' => 'openai']);

    expect($this->fake->requests[0]->provider)->toBe('openai');
});

it('drops a provider that is not allowlisted', function (): void {
    config()->set('translations.ai.allowed_providers', ['openai']);
    Translations::set('messages.x', 'Hello there', 'en');

    Translations::translate('messages.x', 'es', ['provider' => 'evil-co']);

    expect($this->fake->requests[0]->provider)->toBeNull();
});

it('invalidates the glossary cache when terms change', function (): void {
    $es = Locale::query()->where('code', 'es')->first();
    $glossary = app(Glossary::class);

    expect($glossary->pairsFor('open your cart', $es->id))->toBe([]);

    $term = $glossary->define('cart');
    $glossary->translate($term, $es->id, 'carrito');

    expect($glossary->pairsFor('open your cart', $es->id))->toBe(['cart' => 'carrito']);
});
