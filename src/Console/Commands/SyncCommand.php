<?php

declare(strict_types=1);

namespace Syriable\Translations\Console\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Console\Concerns\InteractsWithCatalog;
use Syriable\Translations\Extraction\Extractor;
use Syriable\Translations\Management\Synchronizer;
use Syriable\Translations\Storage\StorageManager;

final class SyncCommand extends Command
{
    use InteractsWithCatalog;

    protected $signature = 'translations:sync
        {--dry-run : Show what would change without writing files}
        {--locale= : Limit synchronization to a single target locale}
        {--prune : Remove keys that are no longer used in code}';

    protected $description = 'Reconcile extracted keys with the translation catalog';

    public function handle(Extractor $extractor, StorageManager $storage): int
    {
        $options = (array) config('translations.sync', []);

        if ($this->option('prune')) {
            $options['prune_unused'] = true;
        }

        $synchronizer = new Synchronizer($storage->driver(), $this->sourceLocale(), $options);

        $report = $synchronizer->sync(
            $extractor->extract($this->extractionPaths()),
            (bool) $this->option('dry-run'),
            $this->option('locale') ?: null,
        );

        if ($report->isEmpty()) {
            $this->info('Catalog already in sync.');

            return self::SUCCESS;
        }

        foreach ($report->locales() as $locale) {
            $this->line(sprintf(
                '  <info>%s</info>: +%d added, -%d pruned',
                $locale,
                count($report->addedFor($locale)),
                count($report->prunedFor($locale)),
            ));
        }

        $verb = $report->dryRun ? 'Would change' : 'Changed';
        $this->info("{$verb} {$report->totalAdded()} added and {$report->totalPruned()} pruned across ".count($report->locales()).' locale(s).');

        if ($report->dryRun) {
            $this->comment('Dry run — no files were written.');
        }

        return self::SUCCESS;
    }
}
