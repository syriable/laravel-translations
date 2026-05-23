<?php

declare(strict_types=1);

namespace Syriable\Translations\Support;

/**
 * Extracts placeholder tokens from a translation value. Recognises Laravel
 * style (":name") and brace style ("{name}") placeholders.
 */
final class Placeholders
{
    /**
     * @return list<string>
     */
    public static function extract(string $text): array
    {
        preg_match_all('/(?<![\w:]):[a-zA-Z][a-zA-Z0-9_]*/', $text, $colon);
        preg_match_all('/\{\s*[a-zA-Z0-9_]+\s*\}/', $text, $brace);

        $normalizedBraces = array_map(
            static fn (string $token): string => (string) preg_replace('/\s+/', '', $token),
            $brace[0],
        );

        $all = array_unique([...$colon[0], ...$normalizedBraces]);
        sort($all);

        return array_values($all);
    }
}
