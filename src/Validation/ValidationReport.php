<?php

declare(strict_types=1);

namespace Syriable\Translations\Validation;

use Syriable\Translations\Domain\Enums\IssueSeverity;

/**
 * The aggregated outcome of a validation run.
 */
final readonly class ValidationReport
{
    /**
     * @param  list<Issue>  $issues
     */
    public function __construct(public array $issues = []) {}

    public function isEmpty(): bool
    {
        return $this->issues === [];
    }

    public function count(): int
    {
        return count($this->issues);
    }

    /**
     * @return list<Issue>
     */
    public function errors(): array
    {
        return array_values(array_filter(
            $this->issues,
            static fn (Issue $issue): bool => $issue->severity === IssueSeverity::Error,
        ));
    }

    public function hasErrors(): bool
    {
        return $this->errors() !== [];
    }

    /**
     * @return list<Issue>
     */
    public function forLocale(string $locale): array
    {
        return array_values(array_filter(
            $this->issues,
            static fn (Issue $issue): bool => $issue->locale === $locale,
        ));
    }

    /**
     * @return array<string, int>
     */
    public function countsBySeverity(): array
    {
        $counts = [];

        foreach ($this->issues as $issue) {
            $counts[$issue->severity->value] = ($counts[$issue->severity->value] ?? 0) + 1;
        }

        return $counts;
    }
}
