<?php

use Syriable\Translations\Ai\AiProviders;

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
