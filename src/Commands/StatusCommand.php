<?php

namespace Syriable\Translations\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Analytics\Insights;

class StatusCommand extends Command
{
    protected $signature = 'translations:status {--locale= : Filter by locale code}';

    protected $description = 'Show translation progress for each locale';

    public function handle(Insights $insights): int
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
}
