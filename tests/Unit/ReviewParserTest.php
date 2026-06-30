<?php

use Syriable\Translations\Ai\ReviewParser;
use Syriable\Translations\Enums\Severity;

it('parses well-formed issues and maps the severity scale', function (): void {
    $issues = (new ReviewParser)->parse([
        ['key' => 'a', 'severity' => 'high', 'description' => 'Breaks placeholder.', 'suggestion' => 'Keep :name.'],
        ['key' => 'b', 'severity' => 'medium', 'description' => 'Awkward wording.'],
        ['key' => 'c', 'severity' => 'low', 'description' => 'Minor nit.', 'suggestion' => ''],
    ]);

    expect($issues)->toHaveCount(3);
    expect($issues[0]->severity)->toBe(Severity::Error);
    expect($issues[0]->suggestion)->toBe('Keep :name.');
    expect($issues[1]->severity)->toBe(Severity::Warning);
    expect($issues[1]->suggestion)->toBeNull();
    expect($issues[2]->severity)->toBe(Severity::Info);
    expect($issues[2]->suggestion)->toBeNull();
});

it('defaults an unknown severity to a warning', function (): void {
    $issues = (new ReviewParser)->parse([
        ['key' => 'a', 'severity' => 'catastrophic', 'description' => 'Something.'],
    ]);

    expect($issues[0]->severity)->toBe(Severity::Warning);
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

it('returns an empty array for non-array input', function (): void {
    expect((new ReviewParser)->parse(null))->toBe([]);
    expect((new ReviewParser)->parse('oops'))->toBe([]);
});
