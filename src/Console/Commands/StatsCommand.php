<?php

declare(strict_types=1);

namespace Syriable\Translations\Console\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Analysis\AnalyticsService;
use Syriable\Translations\Analysis\CompletenessReport;

final class StatsCommand extends Command
{
    protected $signature = 'translations:stats';

    protected $description = 'Show a dashboard overview of catalog completeness and collaboration metadata';

    public function handle(AnalyticsService $analytics): int
    {
        $overview = $analytics->overview();

        $this->info("Source keys: {$overview->totalKeys}");

        if ($overview->completeness !== []) {
            $this->table(
                ['Locale', 'Translated', 'Total', 'Complete'],
                array_map(static fn (CompletenessReport $report): array => [
                    $report->locale,
                    $report->translated,
                    $report->total,
                    $report->percentage().'%',
                ], $overview->completeness),
            );
        }

        $this->line('Pending reviews:   '.$overview->pendingReviews);
        $this->line('Pending hardcoded: '.$overview->pendingHardcoded);
        $this->line('AI runs:           '.$overview->aiRuns);
        $this->line('Activity events:   '.$overview->activityEvents);

        foreach ($overview->issuesBySeverity as $severity => $count) {
            $this->line('Issues ('.$severity.'): '.$count);
        }

        return self::SUCCESS;
    }
}
