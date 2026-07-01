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

it('returns the clean base_value from best() and the framed text from proposed()', function (): void {
    $result = new TranslationResult(
        variants: [
            [
                'value' => 'e.g. "مرحبا"',
                'base_value' => 'مرحبا',
                'confidence' => 0.9,
                'recommended' => true,
                'note' => 'greeting',
            ],
        ],
        provider: 'fake',
    );

    expect($result->best())->toBe('مرحبا');
    expect($result->proposed())->toBe('e.g. "مرحبا"');
});

it('falls best() back to the value when a variant has no base_value', function (): void {
    $result = new TranslationResult(
        variants: [
            ['value' => 'plain', 'confidence' => 0.7, 'recommended' => true, 'note' => null],
        ],
        provider: 'fake',
    );

    expect($result->best())->toBe('plain');
});

it('handles an empty result', function (): void {
    $result = new TranslationResult(variants: [], provider: 'fake');

    expect($result->best())->toBeNull();
    expect($result->proposed())->toBeNull();
    expect($result->note())->toBeNull();
    expect($result->recommended())->toBeNull();
});
