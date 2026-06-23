<?php

use Syriable\Translations\Support\TranslationResult;

it('returns the recommended variant value and note', function (): void {
    $result = new TranslationResult(
        variants: [
            ['value' => 'first', 'confidence' => 0.5, 'recommended' => false, 'note' => 'note one'],
            ['value' => 'second', 'confidence' => 0.9, 'recommended' => true, 'note' => 'note two'],
        ],
        provider: 'fake',
    );

    expect($result->best())->toBe('second');
    expect($result->note())->toBe('note two');
    expect($result->recommended()['value'])->toBe('second');
});

it('falls back to the first variant when none is recommended', function (): void {
    $result = new TranslationResult(
        variants: [
            ['value' => 'only', 'confidence' => 0.7, 'recommended' => false, 'note' => 'because'],
        ],
        provider: 'fake',
    );

    expect($result->best())->toBe('only');
    expect($result->note())->toBe('because');
});

it('handles an empty result', function (): void {
    $result = new TranslationResult(variants: [], provider: 'fake');

    expect($result->best())->toBeNull();
    expect($result->note())->toBeNull();
    expect($result->recommended())->toBeNull();
});
