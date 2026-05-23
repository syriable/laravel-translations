<?php

declare(strict_types=1);

namespace Syriable\Translations\Validation\Rules;

use Syriable\Translations\Contracts\ValidationRule;
use Syriable\Translations\Domain\Enums\IssueSeverity;
use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\Translation;
use Syriable\Translations\Support\PluralRules;
use Syriable\Translations\Validation\Issue;

/**
 * Validates pluralized translations against the *target* language's plural
 * rules rather than the source's.
 *
 * Comparing pipe-segment counts between the source and target produces false
 * positives, because the number of plural forms differs per language (English
 * has 2, Polish 3, Arabic 6). Instead, when the source value is pluralized,
 * the target is expected to carry the number of forms its own language uses.
 *
 * The rule deliberately stays quiet rather than guessing when:
 *  - the source value is not pluralized (no "|");
 *  - either value uses Laravel's explicit form syntax ("{0}", "[2,*]"), which
 *    is valid regardless of segment count;
 *  - the target language's plural form count is unknown.
 */
final class PluralFormRule implements ValidationRule
{
    /**
     * @param  array<string, int>  $expectedForms  locale (or base language) => plural form count, overriding the built-in defaults
     */
    public function __construct(private readonly array $expectedForms = []) {}

    public function id(): string
    {
        return 'plural_form';
    }

    public function validate(Translation $source, Translation $target, Locale $locale): array
    {
        $sourceValue = (string) $source->value;

        if (! str_contains($sourceValue, '|')) {
            return [];
        }

        $targetValue = (string) $target->value;

        if ($this->usesExplicitForms($sourceValue) || $this->usesExplicitForms($targetValue)) {
            return [];
        }

        $expected = $this->expectedFormsFor($locale->code);

        if ($expected === null) {
            return [];
        }

        $actual = count(explode('|', $targetValue));

        if ($actual === $expected) {
            return [];
        }

        return [
            new Issue(
                $this->id(),
                IssueSeverity::Warning,
                $source->key,
                $locale->code,
                "Expected {$expected} plural form(s) for [{$locale->code}], found {$actual}.",
            ),
        ];
    }

    private function expectedFormsFor(string $locale): ?int
    {
        $normalized = strtolower(str_replace('-', '_', trim($locale)));
        $base = explode('_', $normalized, 2)[0];

        return $this->expectedForms[$normalized]
            ?? $this->expectedForms[$base]
            ?? PluralRules::formCount($locale);
    }

    private function usesExplicitForms(string $value): bool
    {
        return preg_match('/\{\s*\d+\s*\}|\[[^\]]*,[^\]]*\]/', $value) === 1;
    }
}
