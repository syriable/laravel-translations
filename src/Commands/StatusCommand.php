<?php

namespace Syriable\Translations\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Analytics\Insights;

class StatusCommand extends Command
{
    protected $signature = 'translations:status
                            {--locale= : Filter locale coverage by locale code}
                            {--bundle= : Filter bundle coverage by bundle name}
                            {--bundles : Show bundle coverage instead of locale coverage}';

    protected $description = 'Show translation progress for each locale or bundle';

    public function handle(Insights $insights): int
    {
        if ($this->option('bundles') || $this->option('bundle')) {
            return $this->displayBundleCoverage($insights);
        }

        return $this->displayLocaleCoverage($insights);
    }

    private function displayLocaleCoverage(Insights $insights): int
    {
        $rows = collect($insights->coverage())
            ->when($this->option('locale'), fn ($rows) => $rows->where('locale', $this->option('locale')))
            ->map(fn (array $row) => [
                $row['locale'],
                $row['total'],
                $row['translated'],
                $row['approved'],
                "{$row['percent']}%",
            ])
            ->all();

        if ($rows === []) {
            $this->components->warn('No target locales found. Run translations:import first.');

            return self::SUCCESS;
        }

        $this->table(['Locale', 'Total', 'Translated', 'Approved', 'Coverage'], $rows);
        $this->components->info("Overall coverage: {$insights->overallCoverage()}%");

        return self::SUCCESS;
    }

    private function displayBundleCoverage(Insights $insights): int
    {
        $rows = collect($insights->bundleCoverage($this->option('bundle')))
            ->map(fn (array $row) => [
                $row['bundle'],
                $row['total'],
                $row['translated'],
                "{$row['percent']}%",
            ])
            ->all();

        if ($rows === []) {
            $this->components->warn('No bundles found. Run translations:import first.');

            return self::SUCCESS;
        }

        $this->table(['Bundle', 'Phrases', 'Translated', 'Coverage'], $rows);

        return self::SUCCESS;
    }
}
