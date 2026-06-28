<?php

namespace Syriable\Translations\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Syriable\Translations\Models\Phrase;
use Syriable\Translations\Support\PlaceholderScanner;

/**
 * Ensures a submitted translation value preserves every placeholder
 * parameter defined by its phrase (e.g. `:name`, `{count}`).
 */
class TranslationPlaceholdersRule implements ValidationRule
{
    public function __construct(private readonly Phrase $phrase) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value) || ! $this->phrase->hasPlaceholders()) {
            return;
        }

        $missing = self::missingPlaceholders($this->phrase, (string) $value);

        if ($missing !== []) {
            $fail(__('translations::messages.validate.missing_placeholders', [
                'placeholders' => implode(', ', $missing),
            ]));
        }
    }

    /**
     * The placeholders the phrase requires but the value is missing.
     *
     * @return list<string>
     */
    public static function missingPlaceholders(Phrase $phrase, string $value): array
    {
        return array_values(array_diff(
            $phrase->placeholderNames(),
            (new PlaceholderScanner)->placeholders($value),
        ));
    }
}
