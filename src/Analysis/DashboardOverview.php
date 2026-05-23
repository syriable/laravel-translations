<?php

declare(strict_types=1);

namespace Syriable\Translations\Analysis;

/**
 * An aggregated snapshot of catalog health and collaboration metadata, intended
 * to back a dashboard. Metadata counts are zero when metadata is disabled.
 */
final readonly class DashboardOverview
{
    /**
     * @param  list<CompletenessReport>  $completeness
     * @param  array<string, int>  $issuesBySeverity
     */
    public function __construct(
        public array $completeness,
        public int $totalKeys,
        public array $issuesBySeverity,
        public int $pendingReviews,
        public int $pendingHardcoded,
        public int $aiRuns,
        public int $aiOutputCharacters,
        public int $activityEvents,
    ) {}
}
