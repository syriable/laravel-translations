<?php

namespace Syriable\Translations\Support;

class PlaceholderScanner
{
    public function placeholders(string $text): array
    {
        preg_match_all('/(?<!\w):([a-zA-Z][a-zA-Z0-9_]*)/', $text, $colon);
        preg_match_all('/\{\s*([a-zA-Z][a-zA-Z0-9_]*)\s*\}/', $text, $brace);

        $found = array_merge(
            array_map(fn (string $name) => ':'.$name, $colon[1]),
            array_map(fn (string $name) => '{'.$name.'}', $brace[1]),
        );

        return array_values(array_unique($found));
    }

    public function hasHtml(string $text): bool
    {
        return (bool) preg_match('/<[a-zA-Z][a-zA-Z0-9]*(\s[^>]*)?\/?>/s', $text);
    }

    public function htmlTags(string $text): array
    {
        preg_match_all('/<\/?([a-zA-Z][a-zA-Z0-9]*)/', $text, $matches);

        return array_map('strtolower', $matches[1]);
    }

    public function isPlural(string $text): bool
    {
        return str_contains($text, '|');
    }

    public function pluralSegments(string $text): int
    {
        return substr_count($text, '|') + 1;
    }

    /**
     * The leading plural selector of each pipe-separated segment. For example
     * `'{0} none|[1,19] some|[20,*] <span>many</span>'` yields
     * `['{0}', '[1,19]', '[20,*]']`. Segments without an explicit selector
     * yield an empty string, so the result stays positionally aligned with the
     * segments.
     *
     * @return list<string>
     */
    public function pluralQualifiers(string $text): array
    {
        return array_map(
            fn (string $segment): string => $this->leadingQualifier($segment),
            explode('|', $text),
        );
    }

    /**
     * The 1-based positions of pipe-separated segments that lack an explicit
     * plural selector when the string mixes selectored and selectorless
     * segments, e.g. `'none|[1,19] some|[20,*] many'` yields `[1]`. Returns an
     * empty array when the selectors are consistent (all present or all
     * absent) or the text is not plural.
     *
     * @return list<int>
     */
    public function missingPluralSelectors(string $text): array
    {
        $qualifiers = $this->pluralQualifiers($text);

        if (array_filter($qualifiers) === []) {
            return [];
        }

        $missing = [];

        foreach ($qualifiers as $index => $qualifier) {
            if ($qualifier === '') {
                $missing[] = $index + 1;
            }
        }

        return $missing;
    }

    private function leadingQualifier(string $segment): string
    {
        if (preg_match('/^\s*([\[{])\s*([^\[\]{}]*?)\s*([\]}])/', $segment, $matches) !== 1) {
            return '';
        }

        return $matches[1].preg_replace('/\s+/', '', $matches[2]).$matches[3];
    }

    public function urls(string $text): array
    {
        preg_match_all('#https?://[^\s<>"\']+#', $text, $matches);

        return array_values(array_unique($matches[0]));
    }

    public function emails(string $text): array
    {
        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $matches);

        return array_values(array_unique($matches[0]));
    }
}
