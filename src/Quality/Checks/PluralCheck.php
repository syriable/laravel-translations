<?php

namespace Syriable\Translations\Quality\Checks;

use Syriable\Translations\Enums\Severity;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Quality\Check;
use Syriable\Translations\Support\Issue;

class PluralCheck extends Check
{
    public function key(): string
    {
        return 'plural';
    }

    public function inspect(Message $message, Message $source): ?Issue
    {
        if (! $this->bothFilled($message, $source)) {
            return null;
        }

        $sourceSelectors = $this->selectors($source->value);

        // Only enforce when the source actually uses explicit plural selectors
        // such as {1} or [2,*]. Simple `a|b` plurals without selectors are not
        // validated here (placeholder/segment checks cover those).
        if (! $this->hasSelectors($sourceSelectors)) {
            return null;
        }

        $targetSelectors = $this->selectors($message->value);

        if ($sourceSelectors === $targetSelectors) {
            return null;
        }

        return new Issue(
            $this->key(),
            Severity::Error,
            'Plural selectors do not match the source string.',
            'Keep the same plural selectors (e.g. {1}, [2,*]) in each segment.',
            false,
            [
                'source' => $this->describe($sourceSelectors),
                'target' => $this->describe($targetSelectors),
            ],
        );
    }

    /**
     * The leading plural selector of each `|`-separated segment, normalised,
     * or null when a segment has no explicit selector.
     *
     * @return list<string|null>
     */
    private function selectors(string $value): array
    {
        $selectors = [];

        foreach (explode('|', $value) as $segment) {
            $selectors[] = $this->selectorFor($segment);
        }

        return $selectors;
    }

    private function selectorFor(string $segment): ?string
    {
        // Matches an exact selector `{n}` or a range selector `[a,b]` where
        // each bound is a number or `*`, optionally surrounded by whitespace.
        $pattern = '/^\s*(\{\s*-?\d+\s*\}|\[\s*(?:-?\d+|\*)\s*,\s*(?:-?\d+|\*)\s*\])/u';

        if (preg_match($pattern, $segment, $matches) !== 1) {
            return null;
        }

        return (string) preg_replace('/\s+/', '', $matches[1]);
    }

    /**
     * @param  list<string|null>  $selectors
     */
    private function hasSelectors(array $selectors): bool
    {
        foreach ($selectors as $selector) {
            if ($selector !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string|null>  $selectors
     * @return list<string>
     */
    private function describe(array $selectors): array
    {
        return array_map(fn (?string $selector): string => $selector ?? '(none)', $selectors);
    }
}
