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

it('fails when explicit selectors are dropped even if the variant count matches', function (): void {
    $phrase = pluralPhrase(true, '{0} There are none|[1,19] There are some|[20,*] <span>There are many</span>');

    expect(pluralFailuresFor($phrase, 'vDVD|SV SDEVAE | AFAWF'))
        ->toBe(['The plural translation must keep the same selectors and numbers as the source ({0} [1,19] [20,*]), got ∅ ∅ ∅.']);
});

it('passes when explicit selectors are preserved exactly', function (): void {
    $phrase = pluralPhrase(true, '{0} There are none|[1,19] There are some|[20,*] <span>There are many</span>');

    expect(pluralFailuresFor($phrase, "{0} Il n'y en a aucun|[1,19] Il y en a quelques-uns|[20,*] <span>Il y en a beaucoup</span>"))
        ->toBe([]);
});

it('fails when an explicit selector number is changed', function (): void {
    $phrase = pluralPhrase(true, '{0} none|[1,19] some|[20,*] many');

    expect(pluralFailuresFor($phrase, '{0} aucun|[1,20] quelques|[20,*] beaucoup'))
        ->toBe(['The plural translation must keep the same selectors and numbers as the source ({0} [1,19] [20,*]), got {0} [1,20] [20,*].']);
});
