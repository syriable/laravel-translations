<?php

declare(strict_types=1);

namespace Syriable\Translations\Console\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Management\CatalogTransfer;
use Syriable\Translations\Storage\StorageManager;

final class ImportCommand extends Command
{
    protected $signature = 'translations:import {--locale= : Limit the import to a single locale}';

    protected $description = 'Load the catalog from language files into the active storage driver';

    public function handle(StorageManager $storage, CatalogTransfer $transfer): int
    {
        $report = $transfer->transfer(
            $storage->driver('file'),
            $storage->driver(),
            $this->option('locale') ?: null,
        );

        $this->info("Imported {$report->totalKeys()} keys across {$report->localeCount()} locale(s).");

        return self::SUCCESS;
    }
}
