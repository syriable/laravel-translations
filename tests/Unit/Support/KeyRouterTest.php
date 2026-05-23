<?php

declare(strict_types=1);

use Syriable\Translations\Domain\Enums\KeyType;
use Syriable\Translations\Support\KeyRouter;

it('routes dotted keys to php groups', function () {
    $routed = (new KeyRouter)->classify('messages.welcome');

    expect($routed->type)->toBe(KeyType::Php)
        ->and($routed->namespace)->toBeNull()
        ->and($routed->group)->toBe('messages')
        ->and($routed->item)->toBe('welcome');
});

it('routes namespaced keys to vendor groups', function () {
    $routed = (new KeyRouter)->classify('package::messages.welcome');

    expect($routed->type)->toBe(KeyType::Php)
        ->and($routed->namespace)->toBe('package')
        ->and($routed->group)->toBe('messages')
        ->and($routed->item)->toBe('welcome');
});

it('routes sentence-like keys to json', function () {
    $routed = (new KeyRouter)->classify('Welcome back, friend!');

    expect($routed->type)->toBe(KeyType::Json)
        ->and($routed->item)->toBe('Welcome back, friend!');
});

it('treats keys without a dot as json', function () {
    expect((new KeyRouter)->classify('welcome')->type)->toBe(KeyType::Json);
});
