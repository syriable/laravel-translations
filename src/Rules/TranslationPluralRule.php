<?php

namespace Syriable\Translations\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Syriable\Translations\Models\Phrase;
use Syriable\Translations\Support\PlaceholderScanner;

/**
 * Ensures a submitted plural translation matches its source. When the source
 * uses explicit selectors (e.g. `{0} none|[1,19] some|[20,*] many`) the
 * translation must preserve every selector and its numbers exactly; otherwise
 * it only has to keep the same number of pipe-separated variants.
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
        $value = (string) $value;

        $sourceQualifiers = $scanner->pluralQualifiers($source);

        // When the source uses explicit selectors ({0}, [1,19], [20,*]) the
        // translation must preserve them exactly: same selectors, numbers and
        // order. This also guarantees an identical variant count.
        if (array_filter($sourceQualifiers) !== []) {
            $valueQualifiers = $scanner->pluralQualifiers($value);

            if ($valueQualifiers !== $sourceQualifiers) {
                $fail(__('translations::messages.validate.plural_qualifiers', [
                    'expected' => $this->renderQualifiers($sourceQualifiers),
                    'actual' => $this->renderQualifiers($valueQualifiers),
                ]));
            }

            return;
        }

        // Otherwise the source is a simple `one|many` plural, so only the
        // number of pipe-separated variants has to match.
        $expected = $scanner->pluralSegments($source);
        $actual = $scanner->pluralSegments($value);

        if ($actual !== $expected) {
            $fail(__('translations::messages.validate.plural_segments', [
                'expected' => $expected,
                'actual' => $actual,
            ]));
        }
    }

    /**
     * @param  list<string>  $qualifiers
     */
    private function renderQualifiers(array $qualifiers): string
    {
        if ($qualifiers === []) {
            return '∅';
        }

        return implode(' ', array_map(
            fn (string $qualifier): string => $qualifier === '' ? '∅' : $qualifier,
            $qualifiers,
        ));
    }
}
