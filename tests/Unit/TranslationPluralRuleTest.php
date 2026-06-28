<?php

use Syriable\Translations\Models\Message;
use Syriable\Translations\Models\Phrase;
use Syriable\Translations\Rules\TranslationPluralRule;

function pluralPhrase(bool $isPlural, ?string $source): Phrase
{
    $phrase = new Phrase(['is_plural' => $isPlural]);
    $phrase->setRelation('sourceMessage', $source === null ? null : new Message(['value' => $source]));

    return $phrase;
}

function pluralFailuresFor(Phrase $phrase, ?string $value): array
{
    $failures = [];

    (new TranslationPluralRule($phrase))->validate(
        'value',
        $value,
        function (string $message) use (&$failures): void {
            $failures[] = $message;
        },
    );

    return $failures;
}

it('passes when the variant counts match', function (): void {
    $phrase = pluralPhrase(true, 'one apple|many apples');

    expect(pluralFailuresFor($phrase, 'تفاحة واحدة|عدة تفاحات'))->toBe([]);
});

it('fails when the variant counts differ', function (): void {
    $phrase = pluralPhrase(true, 'zero|one|many');

    expect(pluralFailuresFor($phrase, 'none|some'))
        ->toBe(['The plural translation must have 3 variants separated by pipes (|), got 2.']);
});

it('skips validation when the phrase is not plural', function (): void {
    $phrase = pluralPhrase(false, 'one|many');

    expect(pluralFailuresFor($phrase, 'single value'))->toBe([]);
});

it('skips validation for an empty value', function (): void {
    $phrase = pluralPhrase(true, 'one|many');

    expect(pluralFailuresFor($phrase, null))->toBe([])
        ->and(pluralFailuresFor($phrase, ''))->toBe([]);
});

it('skips validation when there is no source message', function (): void {
    $phrase = pluralPhrase(true, null);

    expect(pluralFailuresFor($phrase, 'one|two|three'))->toBe([]);
});
