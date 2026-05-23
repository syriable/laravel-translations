<?php

declare(strict_types=1);

namespace Syriable\Translations\Analysis;

/**
 * The outcome of a catalog health analysis: keys used in code but absent from
 * the catalog, keys defined but never used, and per-locale completeness.
 */
final readonly class HealthReport
{
    /**
     * @param  list<string>  $missingKeys  used in code, absent from the source catalog
     * @param  list<string>  $unusedKeys  defined in the source catalog, never used in code
     * @param  array<string, CompletenessReport>  $completeness  keyed by locale code
     */
    public function __construct(
        public array $missingKeys,
        public array $unusedKeys,
        public array $completeness,
    ) {}

    public function hasIssues(): bool
    {
        return $this->missingKeys !== [] || $this->unusedKeys !== [];
    }

    public function isHealthy(): bool
    {
        return ! $this->hasIssues();
    }
}
