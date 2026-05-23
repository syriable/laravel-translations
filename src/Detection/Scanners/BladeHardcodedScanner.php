<?php

declare(strict_types=1);

namespace Syriable\Translations\Detection\Scanners;

use Syriable\Translations\Contracts\HardcodedScanner;
use Syriable\Translations\Detection\DetectedString;

/**
 * Finds hardcoded text in Blade templates: visible text between tags and the
 * value of common translatable attributes. Blade comments, echoes, directives
 * and php blocks are blanked out first (preserving line positions) so only
 * literal markup text remains.
 */
final class BladeHardcodedScanner implements HardcodedScanner
{
    /**
     * @var list<string>
     */
    private const ATTRIBUTES = ['placeholder', 'title', 'alt', 'aria-label'];

    public function extensions(): array
    {
        return ['blade.php'];
    }

    public function scan(string $contents, string $relativePath): array
    {
        $clean = $this->stripBlade($contents);

        $found = [];

        foreach ($this->capture('/>([^<]+)</', $clean) as [$text, $offset]) {
            $value = $this->normalize($text);

            if ($this->isCandidate($value)) {
                $found[] = new DetectedString($value, $relativePath, $this->lineAt($clean, $offset), 'text', 'blade');
            }
        }

        foreach (self::ATTRIBUTES as $attribute) {
            foreach ($this->capture('/'.$attribute.'\s*=\s*"([^"]*)"/i', $clean) as [$text, $offset]) {
                $value = $this->normalize($text);

                if ($this->isCandidate($value)) {
                    $found[] = new DetectedString($value, $relativePath, $this->lineAt($clean, $offset), $attribute, 'blade');
                }
            }
        }

        return $found;
    }

    private function stripBlade(string $blade): string
    {
        $patterns = [
            '/\{\{--.*?--\}\}/s',                              // comments
            '/@php\b.*?@endphp\b/s',                           // php blocks
            '/<\?php.*?\?>/s',                                 // raw php
            '/\{!!.*?!!\}/s',                                  // raw echoes
            '/\{\{.*?\}\}/s',                                  // echoes
            '/@[a-zA-Z]+(?:\s*\((?:[^()]*|\([^()]*\))*\))?/',  // directives
        ];

        foreach ($patterns as $pattern) {
            $blade = (string) preg_replace_callback(
                $pattern,
                fn (array $m): string => $this->blank($m[0]),
                $blade,
            );
        }

        return $blade;
    }

    /**
     * Replace every non-newline character with a space so the string keeps its
     * length and line breaks, preserving offsets for line resolution.
     */
    private function blank(string $value): string
    {
        return (string) preg_replace('/[^\n]/', ' ', $value);
    }

    /**
     * @return list<array{0: string, 1: int}>
     */
    private function capture(string $pattern, string $subject): array
    {
        preg_match_all($pattern, $subject, $matches, PREG_OFFSET_CAPTURE);

        return array_map(
            static fn (array $group): array => [(string) $group[0], (int) $group[1]],
            $matches[1],
        );
    }

    private function normalize(string $text): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    private function isCandidate(string $value): bool
    {
        return mb_strlen($value) >= 2 && preg_match('/\p{L}/u', $value) === 1;
    }

    private function lineAt(string $content, int $offset): int
    {
        return substr_count(substr($content, 0, $offset), "\n") + 1;
    }
}
