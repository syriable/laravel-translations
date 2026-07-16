<?php

namespace Syriable\Translations\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Syriable\Translations\Analytics\Insights;

class StatusCommand extends Command
{
    protected $signature = 'translations:status {--locale= : Filter by locale code} {--bundles : Show per-bundle progress instead of per-locale} {--bundle= : Filter the bundle report to a single bundle}';

    protected $description = 'Show translation progress for each locale or bundle';

    public function handle(Insights $insights): int
    {
        if ($this->option('bundles') || $this->option('bundle')) {
            return $this->bundleReport($insights);
        }

        return $this->localeReport($insights);
    }

    private function localeReport(Insights $insights): int
    {
        $rows = collect($insights->coverage())
            ->when($this->option('locale'), function (Collection $rows): Collection {
                return $rows->where('locale', (string) $this->option('locale'));
            })
            ->map(fn (array $row) => [
                $row['locale'],
                $row['total'],
                $row['translated'],
                $row['approved'],
                "{$row['percent']}%",
            ])
            ->all();

        if ($rows === []) {
            $this->components->warn(__('translations::messages.status.no_locales'));

            return self::SUCCESS;
        }

        $this->table([
            __('translations::messages.status.locale_table.locale'),
            __('translations::messages.status.locale_table.total'),
            __('translations::messages.status.locale_table.translated'),
            __('translations::messages.status.locale_table.approved'),
            __('translations::messages.status.locale_table.coverage'),
        ], $rows);
        $this->components->info(__('translations::messages.status.overall', [
            'percent' => $insights->overallCoverage(),
        ]));

        return self::SUCCESS;
    }

    private function bundleReport(Insights $insights): int
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
            $this->components->warn(__('translations::messages.status.no_bundles'));

            return self::SUCCESS;
        }

        $this->table([
            __('translations::messages.status.bundle_table.bundle'),
            __('translations::messages.status.bundle_table.phrases'),
            __('translations::messages.status.bundle_table.translated'),
            __('translations::messages.status.bundle_table.coverage'),
        ], $rows);

        return self::SUCCESS;
    }
}
