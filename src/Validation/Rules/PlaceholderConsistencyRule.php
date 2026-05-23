<?php

declare(strict_types=1);

namespace Syriable\Translations\Validation\Rules;

use Syriable\Translations\Contracts\ValidationRule;
use Syriable\Translations\Domain\Enums\IssueSeverity;
use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\Translation;
use Syriable\Translations\Support\Placeholders;
use Syriable\Translations\Validation\Issue;

/**
 * Ensures a translation preserves the placeholders present in its source value.
 */
final class PlaceholderConsistencyRule implements ValidationRule
{
    public function id(): string
    {
        return 'placeholder_consistency';
    }

    public function validate(Translation $source, Translation $target, Locale $locale): array
    {
        $sourcePlaceholders = Placeholders::extract((string) $source->value);
        $targetPlaceholders = Placeholders::extract((string) $target->value);

        $issues = [];

        $missing = array_diff($sourcePlaceholders, $targetPlaceholders);
        $extra = array_diff($targetPlaceholders, $sourcePlaceholders);

        if ($missing !== []) {
            $issues[] = new Issue(
                $this->id(),
                IssueSeverity::Error,
                $source->key,
                $locale->code,
                'Missing placeholders: '.implode(', ', $missing).'.',
            );
        }

        if ($extra !== []) {
            $issues[] = new Issue(
                $this->id(),
                IssueSeverity::Warning,
                $source->key,
                $locale->code,
                'Unexpected placeholders: '.implode(', ', $extra).'.',
            );
        }

        return $issues;
    }
}
