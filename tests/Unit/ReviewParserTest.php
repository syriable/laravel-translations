<?php

use Syriable\Translations\Ai\ReviewParser;
use Syriable\Translations\Enums\ReviewSeverity;

it('parses well-formed issues and resolves the priority', function (): void {
    $issues = (new ReviewParser)->parse([
        ['key' => 'a', 'severity' => 'high', 'description' => 'Breaks placeholder.', 'suggestion' => 'Keep :name.'],
        ['key' => 'b', 'severity' => 'medium', 'description' => 'Awkward wording.'],
        ['key' => 'c', 'severity' => 'low', 'description' => 'Minor nit.', 'suggestion' => ''],
    ]);

    expect($issues)->toHaveCount(3);
    expect($issues[0]->severity)->toBe(ReviewSeverity::High);
    expect($issues[0]->suggestion)->toBe('Keep :name.');
    expect($issues[1]->severity)->toBe(ReviewSeverity::Medium);
    expect($issues[1]->suggestion)->toBeNull();
    expect($issues[2]->severity)->toBe(ReviewSeverity::Low);
    expect($issues[2]->suggestion)->toBeNull();
});

it('defaults an unknown priority to medium', function (): void {
    $issues = (new ReviewParser)->parse([
        ['key' => 'a', 'severity' => 'catastrophic', 'description' => 'Something.'],
    ]);

    expect($issues[0]->severity)->toBe(ReviewSeverity::Medium);
});

it('drops issues without a key or description', function (): void {
    $issues = (new ReviewParser)->parse([
        ['key' => '', 'severity' => 'high', 'description' => 'No key.'],
        ['key' => 'a', 'severity' => 'high', 'description' => ''],
        'not-an-array',
        ['key' => 'a', 'severity' => 'high', 'description' => 'Valid.'],
    ]);

    expect($issues)->toHaveCount(1);
    expect($issues[0]->key)->toBe('a');
});

it('discards issues whose key was not part of the reviewed set', function (): void {
    $issues = (new ReviewParser)->parse([
        ['key' => 'real', 'severity' => 'low', 'description' => 'Real issue.'],
        ['key' => 'hallucinated', 'severity' => 'high', 'description' => 'Made-up key.'],
    ], allowedKeys: ['real']);

    expect($issues)->toHaveCount(1);
    expect($issues[0]->key)->toBe('real');
});

it('uses the model-provided base_suggestion and keeps suggestion as proposed', function (): void {
    $issues = (new ReviewParser)->parse([
        [
            'key' => 'a',
            'severity' => 'medium',
            'description' => 'Too informal.',
            'suggestion' => 'Use the formal register, e.g. "مرحباً".',
            'base_suggestion' => 'مرحباً',
        ],
    ]);

    expect($issues[0]->suggestion)->toBe('Use the formal register, e.g. "مرحباً".');
    expect($issues[0]->baseSuggestion)->toBe('مرحباً');
});

it('extracts a clean base_suggestion from a framed suggestion when the model omits it', function (): void {
    $suggestion = 'Change it to, for example: "يجب قبول حقل :attribute عندما يكون :other هو :value." and keep the placeholders.';

    $issues = (new ReviewParser)->parse([
        ['key' => 'a', 'severity' => 'high', 'description' => 'Placeholder issue.', 'suggestion' => $suggestion],
    ]);

    expect($issues[0]->suggestion)->toBe($suggestion);
    expect($issues[0]->baseSuggestion)->toBe('يجب قبول حقل :attribute عندما يكون :other هو :value.');
});

it('leaves base_suggestion null when there is no suggestion', function (): void {
    $issues = (new ReviewParser)->parse([
        ['key' => 'a', 'severity' => 'low', 'description' => 'Minor nit.'],
    ]);

    expect($issues[0]->suggestion)->toBeNull();
    expect($issues[0]->baseSuggestion)->toBeNull();
});

it('returns an empty array for non-array input', function (): void {
    expect((new ReviewParser)->parse(null))->toBe([]);
    expect((new ReviewParser)->parse('oops'))->toBe([]);
});
