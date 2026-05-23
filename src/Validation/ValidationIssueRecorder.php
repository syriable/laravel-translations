<?php

declare(strict_types=1);

namespace Syriable\Translations\Validation;

use Syriable\Translations\Models\ValidationIssue;

/**
 * Persists validation findings to the database so the management UI can list
 * and act on them. Recording is a no-op when metadata is disabled.
 */
final class ValidationIssueRecorder
{
    public function enabled(): bool
    {
        return config('translations.metadata.enabled', true) === true;
    }

    /**
     * Replace the stored issues for a single translation.
     */
    public function recordForKey(string $locale, string $key, ValidationReport $report): void
    {
        if (! $this->enabled()) {
            return;
        }

        ValidationIssue::query()->forKey($locale, $key)->delete();
        $this->insert($report->issues);
    }

    /**
     * Replace the stored issues for the given locales with a fresh report.
     *
     * @param  list<string>  $locales
     */
    public function recordForLocales(ValidationReport $report, array $locales): void
    {
        if (! $this->enabled()) {
            return;
        }

        foreach ($locales as $locale) {
            ValidationIssue::query()->forLocale($locale)->delete();
        }

        $this->insert($report->issues);
    }

    /**
     * @param  list<Issue>  $issues
     */
    private function insert(array $issues): void
    {
        foreach ($issues as $issue) {
            ValidationIssue::query()->create([
                'locale' => $issue->locale,
                'translation_key' => $issue->key,
                'key_hash' => ValidationIssue::hashKey($issue->key),
                'check' => $issue->rule,
                'severity' => $issue->severity,
                'message' => $issue->message,
                'suggestion' => $issue->suggestion,
                'auto_fixable' => false,
            ]);
        }
    }
}
