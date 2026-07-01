<?php

namespace Syriable\Translations\Ai;

use Syriable\Translations\Enums\ReviewSeverity;
use Syriable\Translations\Support\ReviewIssue;

class ReviewParser
{
    /**
     * Turn the raw structured-output issues into normalized ReviewIssue objects:
     * coerce the fields, map the severity, resolve a clean copy/store-ready
     * base_suggestion, drop empty descriptions, and discard any issue whose key
     * was not part of the reviewed set (a hallucinated key).
     *
     * @param  array<int, string>  $allowedKeys  The dotted keys that were sent for review.
     * @return array<int, ReviewIssue>
     */
    public function parse(mixed $issues, array $allowedKeys = []): array
    {
        if (! is_array($issues)) {
            return [];
        }

        $allowed = $allowedKeys === [] ? null : array_flip($allowedKeys);
        $parsed = [];

        foreach ($issues as $issue) {
            if (! is_array($issue)) {
                continue;
            }

            $key = trim((string) ($issue['key'] ?? ''));
            $description = trim((string) ($issue['description'] ?? ''));

            if ($key === '' || $description === '') {
                continue;
            }

            if ($allowed !== null && ! isset($allowed[$key])) {
                continue;
            }

            $suggestion = trim((string) ($issue['suggestion'] ?? ''));
            $baseSuggestion = SuggestionCleaner::plain($issue['base_suggestion'] ?? null, $suggestion);

            $parsed[] = new ReviewIssue(
                key: $key,
                severity: ReviewSeverity::fromModel($issue['severity'] ?? null),
                description: $description,
                suggestion: $suggestion === '' ? null : $suggestion,
                baseSuggestion: $baseSuggestion === '' ? null : $baseSuggestion,
            );
        }

        return $parsed;
    }
}
