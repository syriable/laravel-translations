<?php

declare(strict_types=1);

namespace Syriable\Translations\Validation\Rules;

use Syriable\Translations\Contracts\ValidationRule;
use Syriable\Translations\Domain\Enums\IssueSeverity;
use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\Translation;
use Syriable\Translations\Validation\Issue;

/**
 * Ensures a translation uses the same set of HTML tags as its source value.
 */
final class HtmlTagRule implements ValidationRule
{
    public function id(): string
    {
        return 'html_tags';
    }

    public function validate(Translation $source, Translation $target, Locale $locale): array
    {
        $sourceTags = $this->tags((string) $source->value);

        if ($sourceTags === []) {
            return [];
        }

        if ($sourceTags === $this->tags((string) $target->value)) {
            return [];
        }

        return [
            new Issue(
                $this->id(),
                IssueSeverity::Warning,
                $source->key,
                $locale->code,
                'HTML tags differ from the source value.',
            ),
        ];
    }

    /**
     * @return list<string>
     */
    private function tags(string $text): array
    {
        preg_match_all('/<\/?([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/', $text, $matches);

        $tags = array_map('strtolower', $matches[1]);
        sort($tags);

        return $tags;
    }
}
