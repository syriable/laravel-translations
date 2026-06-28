<?php

use Syriable\Translations\Models\Phrase;
use Syriable\Translations\Rules\TranslationPlaceholdersRule;

function failuresFor(Phrase $phrase, ?string $value): array
{
    $failures = [];

    (new TranslationPlaceholdersRule($phrase))->validate(
        'value',
        $value,
        function (string $message) use (&$failures): void {
            $failures[] = $message;
        },
    );

    return $failures;
}

it('passes when every required placeholder is present', function (): void {
    $phrase = new Phrase(['placeholders' => [':name', '{count}']]);

    expect(failuresFor($phrase, 'Hi {count} messages for :name'))->toBe([]);
});

it('fails listing the placeholders that are missing', function (): void {
    $phrase = new Phrase(['placeholders' => [':name', '{count}']]);

    expect(failuresFor($phrase, 'Hi :name'))
        ->toBe(['The translation is missing required placeholders: {count}.']);
});

it('skips validation when the phrase has no placeholders', function (): void {
    $phrase = new Phrase(['placeholders' => []]);

    expect(failuresFor($phrase, 'Anything goes'))->toBe([]);
});

it('skips validation for an empty value', function (): void {
    $phrase = new Phrase(['placeholders' => [':name']]);

    expect(failuresFor($phrase, null))->toBe([])
        ->and(failuresFor($phrase, ''))->toBe([]);
});

it('does not treat a longer token as a present placeholder', function (): void {
    $phrase = new Phrase(['placeholders' => [':name']]);

    expect(failuresFor($phrase, 'Welcome :names'))
        ->toBe(['The translation is missing required placeholders: :name.']);
});

it('exposes the missing placeholders for reuse', function (): void {
    $phrase = new Phrase(['placeholders' => [':name', '{count}']]);

    expect(TranslationPlaceholdersRule::missingPlaceholders($phrase, 'Hi :name'))
        ->toBe(['{count}']);
});
