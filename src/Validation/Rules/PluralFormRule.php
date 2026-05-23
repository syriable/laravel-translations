<?php

declare(strict_types=1);

namespace Syriable\Translations\Validation\Rules;

use Syriable\Translations\Contracts\ValidationRule;
use Syriable\Translations\Domain\Enums\IssueSeverity;
use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\Translation;
use Syriable\Translations\Validation\Issue;

/**
 * Ensures a pluralized translation has the same number of pipe-separated
 * segments as its source value.
 */
final class PluralFormRule implements ValidationRule
{
    public function id(): string
    {
        return 'plural_form';
    }

    public function validate(Translation $source, Translation $target, Locale $locale): array
    {
        if (! str_contains((string) $source->value, '|')) {
            return [];
        }

        $expected = count(explode('|', (string) $source->value));
        $actual = count(explode('|', (string) $target->value));

        if ($expected === $actual) {
            return [];
        }

        return [
            new Issue(
                $this->id(),
                IssueSeverity::Warning,
                $source->key,
                $locale->code,
                "Expected {$expected} plural segments, found {$actual}.",
            ),
        ];
    }
}
