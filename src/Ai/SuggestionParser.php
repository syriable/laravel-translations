<?php

namespace Syriable\Translations\Ai;

class SuggestionParser
{
    /**
     * Turn the raw structured-output suggestions into normalized variants:
     * unwrap any embedded JSON payloads, coerce the fields, drop empties and
     * guarantee exactly one recommended variant.
     *
     * @param  array<int, mixed>  $suggestions
     * @return array<int, array{value: string, confidence: float|null, recommended: bool, note: string|null}>
     */
    public function parse(array $suggestions): array
    {
        $variants = collect($this->extractSuggestions($suggestions))
            ->map(fn (array $suggestion) => [
                'value' => trim((string) ($suggestion['value'] ?? '')),
                'confidence' => isset($suggestion['confidence']) ? (float) $suggestion['confidence'] : null,
                'recommended' => (bool) ($suggestion['recommended'] ?? false),
                'note' => isset($suggestion['note']) && (string) $suggestion['note'] !== ''
                    ? (string) $suggestion['note']
                    : null,
            ])
            ->filter(fn (array $variant) => $variant['value'] !== '')
            ->values()
            ->all();

        return $this->normalizeRecommended($variants);
    }

    /**
     * Flatten the raw suggestions. Some providers/models ignore the structured
     * schema and return the whole suggestion list encoded as a JSON string inside
     * a single suggestion's value. Detect and unwrap that so each suggestion is
     * parsed individually instead of leaking raw JSON into the translation.
     *
     * @param  array<int, mixed>  $suggestions
     * @return array<int, array<string, mixed>>
     */
    private function extractSuggestions(array $suggestions): array
    {
        $flattened = [];

        foreach ($suggestions as $suggestion) {
            if (! is_array($suggestion)) {
                continue;
            }

            $nested = is_string($suggestion['value'] ?? null)
                ? $this->decodeNestedSuggestions($suggestion['value'])
                : null;

            if ($nested !== null) {
                array_push($flattened, ...$nested);

                continue;
            }

            $flattened[] = $suggestion;
        }

        return $flattened;
    }

    /**
     * Decode a value that is itself a JSON list (or `{"suggestions": [...]}`
     * object) of suggestion objects. Returns null when the value is a plain
     * translation rather than an embedded payload.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function decodeNestedSuggestions(string $value): ?array
    {
        $trimmed = trim($value);

        // Strip a fenced ```json … ``` wrapper if the model added one.
        if (str_starts_with($trimmed, '```')) {
            $trimmed = trim((string) preg_replace('/^```[a-zA-Z]*|```$/', '', $trimmed));
        }

        if (! str_starts_with($trimmed, '[') && ! str_starts_with($trimmed, '{')) {
            return null;
        }

        $decoded = json_decode($trimmed, true);

        if (isset($decoded['suggestions']) && is_array($decoded['suggestions'])) {
            $decoded = $decoded['suggestions'];
        }

        if (! is_array($decoded) || $decoded === []) {
            return null;
        }

        $items = array_values(array_filter(
            $decoded,
            fn ($item) => is_array($item) && array_key_exists('value', $item),
        ));

        return $items === [] ? null : $items;
    }

    /**
     * Ensure exactly one variant is flagged as recommended. The model's choice is
     * honoured when it marks a single suggestion; otherwise the highest-confidence
     * variant wins, falling back to the first one.
     *
     * @param  array<int, array{value: string, confidence: float|null, recommended: bool, note: string|null}>  $variants
     * @return array<int, array{value: string, confidence: float|null, recommended: bool, note: string|null}>
     */
    private function normalizeRecommended(array $variants): array
    {
        if ($variants === []) {
            return $variants;
        }

        $flagged = array_keys(array_filter($variants, fn (array $variant) => $variant['recommended']));

        $winner = count($flagged) === 1
            ? $flagged[0]
            : $this->highestConfidenceIndex($variants);

        foreach ($variants as $index => &$variant) {
            $variant['recommended'] = $index === $winner;
        }

        return $variants;
    }

    /**
     * @param  array<int, array{confidence: float|null}>  $variants
     */
    private function highestConfidenceIndex(array $variants): int
    {
        $winner = 0;

        foreach ($variants as $index => $variant) {
            if (($variant['confidence'] ?? 0) > ($variants[$winner]['confidence'] ?? 0)) {
                $winner = $index;
            }
        }

        return $winner;
    }
}
