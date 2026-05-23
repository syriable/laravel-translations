<?php

declare(strict_types=1);

namespace Syriable\Translations\Validation\Rules;

use Syriable\Translations\Contracts\ValidationRule;
use Syriable\Translations\Domain\Enums\IssueSeverity;
use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\Translation;
use Syriable\Translations\Glossary\GlossaryEntry;
use Syriable\Translations\Glossary\GlossaryService;
use Syriable\Translations\Validation\Issue;

/**
 * Flags translations that use a glossary term in the source but fail to use its
 * agreed translation in the target, keeping terminology consistent.
 */
final class GlossaryConsistencyRule implements ValidationRule
{
    public function __construct(private readonly GlossaryService $glossary) {}

    public function id(): string
    {
        return 'glossary_consistency';
    }

    public function validate(Translation $source, Translation $target, Locale $locale): array
    {
        $entries = $this->glossary->forLocale($locale->code);

        if ($entries === []) {
            return [];
        }

        $sourceValue = (string) $source->value;
        $targetValue = (string) $target->value;

        $issues = [];

        foreach ($entries as $entry) {
            if (! $this->contains($sourceValue, $entry->sourceTerm, $entry)) {
                continue;
            }

            if ($this->contains($targetValue, $entry->translation, $entry)) {
                continue;
            }

            $issues[] = new Issue(
                $this->id(),
                IssueSeverity::Warning,
                $source->key,
                $locale->code,
                "Glossary term \"{$entry->sourceTerm}\" should be translated as \"{$entry->translation}\".",
                $entry->translation,
            );
        }

        return $issues;
    }

    private function contains(string $haystack, string $needle, GlossaryEntry $entry): bool
    {
        if ($needle === '') {
            return true;
        }

        if ($entry->exactMatch) {
            $flags = $entry->caseSensitive ? 'u' : 'iu';

            return preg_match('/\b'.preg_quote($needle, '/').'\b/'.$flags, $haystack) === 1;
        }

        return $entry->caseSensitive
            ? str_contains($haystack, $needle)
            : stripos($haystack, $needle) !== false;
    }
}
