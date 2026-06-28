<?php

namespace Syriable\Translations\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Syriable\Translations\Models\Phrase;
use Syriable\Translations\Support\PlaceholderScanner;

/**
 * Ensures a submitted plural translation has the same number of pipe-separated
 * variants (e.g. `one apple|many apples`) as the phrase's source message.
 */
class TranslationPluralRule implements ValidationRule
{
    public function __construct(private readonly Phrase $phrase) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value) || ! $this->phrase->is_plural) {
            return;
        }

        $source = $this->phrase->sourceMessage?->value;

        if (blank($source)) {
            return;
        }

        $scanner = new PlaceholderScanner;
        $expected = $scanner->pluralSegments($source);
        $actual = $scanner->pluralSegments((string) $value);

        if ($actual !== $expected) {
            $fail(__('translations::messages.validate.plural_segments', [
                'expected' => $expected,
                'actual' => $actual,
            ]));
        }
    }
}
