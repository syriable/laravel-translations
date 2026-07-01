<?php

namespace Syriable\Translations\Ai;

class SuggestionParser
{
    /**
     * Turn the raw structured-output suggestions into normalized variants:
     * unwrap any embedded JSON payloads, coerce the fields, drop empties and
     * guarantee exactly one recommended variant.
     *
     * @return array<int, array{value: string, base_value: string, confidence: float|null, recommended: bool, note: string|null}>
     */
    public function parse(mixed $suggestions): array
    {
        // Defend against models returning the whole payload as a string or a
        // single object rather than the expected list of suggestion objects.
        if (is_string($suggestions)) {
            $suggestions = [$suggestions];
        } elseif (is_array($suggestions) && array_key_exists('value', $suggestions)) {
            $suggestions = [$suggestions];
        } elseif (! is_array($suggestions)) {
            return [];
        }

        $variants = collect($this->extractSuggestions($suggestions))
            ->map(function (array $suggestion): array {
                $value = trim((string) ($suggestion['value'] ?? ''));

                return [
                    'value' => $value,
                    'base_value' => $this->baseValue($suggestion['base_value'] ?? null, $value),
                    'confidence' => isset($suggestion['confidence']) ? (float) $suggestion['confidence'] : null,
                    'recommended' => (bool) ($suggestion['recommended'] ?? false),
                    'note' => isset($suggestion['note']) && (string) $suggestion['note'] !== ''
                        ? (string) $suggestion['note']
                        : null,
                ];
            })
            ->filter(fn (array $variant) => $variant['value'] !== '')
            ->values()
            ->all();

        return $this->normalizeRecommended($variants);
    }

    /**
     * Resolve the clean, copy/store-ready translation. Prefer the model's
     * dedicated `base_value` field; when it is missing, recover the translation
     * from the (possibly framed) `value` by unwrapping surrounding quotes or
     * pulling the quoted translation out of an example like
     * `Translate to Arabic, for example: "…"`.
     */
    private function baseValue(mixed $base, string $value): string
    {
        $base = $this->unquote(trim((string) $base));

        if ($base !== '') {
            return $base;
        }

        $unquoted = $this->unquote($value);

        if ($unquoted !== $value) {
            return $unquoted;
        }

        return $this->extractFramedTranslation($value) ?? $value;
    }

    /**
     * Strip a single matched pair of surrounding quotes (straight, curly,
     * single or guillemets) and trim the result.
     */
    private function unquote(string $text): string
    {
        foreach ([['"', '"'], ['“', '”'], ["'", "'"], ['«', '»']] as [$open, $close]) {
            if (mb_strlen($text) >= mb_strlen($open) + mb_strlen($close)
                && str_starts_with($text, $open)
                && str_ends_with($text, $close)) {
                return trim(mb_substr($text, mb_strlen($open), mb_strlen($text) - mb_strlen($open) - mb_strlen($close)));
            }
        }

        return $text;
    }

    /**
     * Pull the translation out of a framed value such as
     * `Translate the text to Arabic, for example: "الترجمة."`. Only fires when a
     * quoted run follows a colon, so a translation that legitimately contains a
     * quoted phrase (e.g. `He said "hi"`) is left untouched.
     */
    private function extractFramedTranslation(string $value): ?string
    {
        if (preg_match('/:\s*["“«\'](.+?)["”»\']/u', $value, $matches) !== 1) {
            return null;
        }

        $inner = trim($matches[1]);

        return $inner === '' ? null : $inner;
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
            // Some models return suggestions as bare strings instead of objects.
            // Treat the string as the value, after first checking it isn't an
            // embedded JSON payload of suggestions.
            if (is_string($suggestion)) {
                $suggestion = ['value' => $suggestion];
            }

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

        // Models frequently emit invalid JSON here — typically unescaped double
        // quotes inside note/value strings (e.g. 使用"凭据"). Repair and retry.
        if (! is_array($decoded)) {
            $decoded = json_decode($this->repairJson($trimmed), true);
        }

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
     * Best-effort repair of JSON that contains unescaped double quotes inside
     * string values. A quote is treated as structural (it closes the string)
     * only when the next non-space character is a JSON delimiter (`:,}]`) or the
     * end of input; any other quote is escaped so the string keeps its content.
     * Quote bytes are ASCII, so iterating bytes is safe for UTF-8 input.
     */
    private function repairJson(string $json): string
    {
        $result = '';
        $length = strlen($json);
        $inString = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $json[$i];

            if (! $inString) {
                $result .= $char;

                if ($char === '"') {
                    $inString = true;
                }

                continue;
            }

            if ($char === '\\') {
                // Preserve an existing escape sequence verbatim.
                $result .= $char;

                if ($i + 1 < $length) {
                    $result .= $json[++$i];
                }

                continue;
            }

            if ($char === '"') {
                $next = $i + 1;

                while ($next < $length && ctype_space($json[$next])) {
                    $next++;
                }

                $following = $next < $length ? $json[$next] : '';

                if ($following === '' || in_array($following, [':', ',', '}', ']'], true)) {
                    $inString = false;
                    $result .= $char;
                } else {
                    // A stray quote inside the string: escape it.
                    $result .= '\\"';
                }

                continue;
            }

            $result .= $char;
        }

        return $result;
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
