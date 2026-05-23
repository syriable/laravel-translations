<?php

declare(strict_types=1);

namespace Syriable\Translations\Console\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Management\CatalogTransfer;
use Syriable\Translations\Storage\StorageManager;

final class ExportCommand extends Command
{
    protected $signature = 'translations:export {--locale= : Limit the export to a single locale}';

    protected $description = 'Write the catalog from the active driver back to language files, normalized';

    public function handle(StorageManager $storage, CatalogTransfer $transfer): int
    {
        $report = $transfer->transfer(
            $storage->driver(),
            $storage->driver('file'),
            $this->option('locale') ?: null,
        );

        $this->info("Exported {$report->totalKeys()} keys across {$report->localeCount()} locale(s).");

        return self::SUCCESS;
    }
}
