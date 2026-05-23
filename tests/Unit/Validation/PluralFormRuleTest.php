<?php

declare(strict_types=1);

use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\Translation;
use Syriable\Translations\Validation\Rules\PluralFormRule;

function checkPlural(string $source, string $target, string $locale, array $overrides = []): array
{
    return (new PluralFormRule($overrides))->validate(
        new Translation('apples', $source),
        new Translation('apples', $target),
        new Locale($locale),
    );
}

it('passes when the target has the form count its own language uses', function (string $target, string $locale) {
    expect(checkPlural('one apple|many apples', $target, $locale))->toBe([]);
})->with([
    'english (2)' => ['une pomme|des pommes', 'fr'],
    'polish (3)' => ['jabłko|jabłka|jabłek', 'pl'],
    'arabic (6)' => ['لا تفاح|تفاحة|تفاحتان|تفاحات|تفاحة كثيرة|تفاح', 'ar'],
    'no-plural language (1)' => ['リンゴ', 'ja'],
]);

it('does not flag an arabic translation with more forms than the english source', function () {
    // The original bug: 2 source segments vs 6 target segments was a false positive.
    $issues = checkPlural(
        'one apple|many apples',
        'لا تفاح|تفاحة|تفاحتان|تفاحات|تفاحة كثيرة|تفاح',
        'ar',
    );

    expect($issues)->toBe([]);
});

it('flags a target whose form count is wrong for its language', function (string $target, string $locale, int $expected, int $actual) {
    $issues = checkPlural('one apple|many apples', $target, $locale);

    expect($issues)->toHaveCount(1)
        ->and($issues[0]->rule)->toBe('plural_form')
        ->and($issues[0]->severity->value)->toBe('warning')
        ->and($issues[0]->message)->toContain((string) $expected)
        ->and($issues[0]->message)->toContain((string) $actual);
})->with([
    'french missing a form' => ['une pomme', 'fr', 2, 1],
    'polish with only two forms' => ['jabłko|jabłka', 'pl', 3, 2],
    'arabic with too few forms' => ['تفاحة|تفاح', 'ar', 6, 2],
]);

it('skips validation when the source value is not pluralized', function () {
    // A stray pipe in a non-plural target must not be validated.
    expect(checkPlural('Just one apple', 'une | deux | trois', 'fr'))->toBe([]);
});

it('skips validation when the value uses explicit plural syntax', function (string $source, string $target) {
    expect(checkPlural($source, $target, 'ar'))->toBe([]);
})->with([
    'explicit exact source' => ['{0} none|{1} one|[2,*] many', 'تفاحة|تفاح'],
    'explicit interval target' => ['one apple|many apples', '{0} لا شيء|[1,*] تفاح'],
]);

it('skips validation for languages with an unknown plural form count', function () {
    expect(checkPlural('one apple|many apples', 'whatever|something|else', 'xx'))->toBe([]);
});

it('honours per-locale overrides for unknown or custom languages', function () {
    // "xx" is unknown to the built-in table; an override teaches it 3 forms.
    expect(checkPlural('a|b', 'one|two|three', 'xx', ['xx' => 3]))->toBe([]);

    $issues = checkPlural('a|b', 'one|two', 'xx', ['xx' => 3]);

    expect($issues)->toHaveCount(1)
        ->and($issues[0]->message)->toContain('3');
});

it('resolves region subtags to their base language', function () {
    // pt_BR and en-US fall back to pt/en (2 forms).
    expect(checkPlural('one apple|many apples', 'uma maçã|muitas maçãs', 'pt_BR'))->toBe([])
        ->and(checkPlural('one apple|many apples', 'one apple|many apples', 'en-US'))->toBe([]);
});
