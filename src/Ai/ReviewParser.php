<?php

namespace Syriable\Translations\Ai;

use Syriable\Translations\Enums\Severity;
use Syriable\Translations\Support\ReviewIssue;

class ReviewParser
{
    /**
     * The AI reports severities as low/medium/high; map them onto the package's
     * own Severity scale so review issues read the same as deterministic ones.
     */
    private const SEVERITY = [
        'high' => Severity::Error,
        'medium' => Severity::Warning,
        'low' => Severity::Info,
    ];

    /**
     * Turn the raw structured-output issues into normalized ReviewIssue objects:
     * coerce the fields, map the severity, drop empty descriptions, and discard
     * any issue whose key was not part of the reviewed set (a hallucinated key).
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

            $parsed[] = new ReviewIssue(
                key: $key,
                severity: $this->severity($issue['severity'] ?? null),
                description: $description,
                suggestion: $suggestion === '' ? null : $suggestion,
            );
        }

        return $parsed;
    }

    private function severity(mixed $severity): Severity
    {
        return self::SEVERITY[strtolower(trim((string) $severity))] ?? Severity::Warning;
    }
}
