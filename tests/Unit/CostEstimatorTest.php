<?php

use Syriable\Translations\Ai\CostEstimator;

beforeEach(function (): void {
    config()->set('translations.ai.cost_rates', [
        'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
    ]);
    $this->estimator = new CostEstimator;
});

it('computes cost from per-million-character rates', function (): void {
    expect($this->estimator->estimate('gpt-4o-mini', 1_000_000, 1_000_000))
        ->toBe(0.75);
});

it('scales linearly below a million characters', function (): void {
    expect($this->estimator->estimate('gpt-4o-mini', 500_000, 0))
        ->toBe(0.075);
});

it('returns zero for empty input', function (): void {
    expect($this->estimator->estimate('gpt-4o-mini', 0, 0))->toBe(0.0);
});

it('falls back to a default rate for an unknown model', function (): void {
    expect($this->estimator->estimate('mystery-model', 1_000_000, 1_000_000))
        ->toBe(1.0);
});

it('estimates a batch including an output expansion factor', function (): void {
    $cost = $this->estimator->estimateTexts('gpt-4o-mini', ['aaaa', 'bbbbbb'], expansion: 1.0);

    expect($cost)->toBe($this->estimator->estimate('gpt-4o-mini', 10, 10));
});
