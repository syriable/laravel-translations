<?php

use Syriable\Translations\Ai\AiProviders;
use Syriable\Translations\Enums\AiProvider;

beforeEach(function (): void {
    config()->set('translations.ai.allowed_providers', ['openai', 'anthropic']);
});

it('passes through an allowlisted provider', function (): void {
    expect(AiProviders::sanitize('anthropic'))->toBe('anthropic');
});

it('drops a provider that is not allowlisted', function (): void {
    expect(AiProviders::sanitize('evil-co'))->toBeNull();
});

it('passes null through unchanged', function (): void {
    expect(AiProviders::sanitize(null))->toBeNull();
});

it('returns only allowlisted providers that have a configured key', function (): void {
    config()->set('translations.ai.allowed_providers', ['openai', 'anthropic', 'gemini']);
    config()->set('ai.providers', [
        'openai' => ['driver' => 'openai', 'key' => 'sk-test'],
        'anthropic' => ['driver' => 'anthropic', 'key' => null],
        'gemini' => ['driver' => 'gemini', 'key' => ''],
    ]);

    $usable = AiProviders::usable();

    expect($usable->all())->toBe([AiProvider::OpenAI])
        ->and($usable->first())->toBeInstanceOf(AiProvider::class);
});

it('treats ollama as usable without an api key', function (): void {
    config()->set('translations.ai.allowed_providers', ['ollama', 'openai']);
    config()->set('ai.providers', [
        'ollama' => ['driver' => 'ollama', 'key' => ''],
        'openai' => ['driver' => 'openai', 'key' => null],
    ]);

    expect(AiProviders::usable()->all())->toBe([AiProvider::Ollama]);
});

it('returns nothing when no provider config is present', function (): void {
    config()->set('translations.ai.allowed_providers', ['openai']);
    config()->set('ai', null);

    expect(AiProviders::usable())->toBeEmpty();
});
