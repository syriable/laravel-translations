<?php

namespace Syriable\Translations\Listeners;

use Syriable\Translations\Events\ImportFinished;
use Syriable\Translations\Jobs\ScanUsageJob;

class ScanUsageAfterImport
{
    public function handle(ImportFinished $event): void
    {
        if (! config('translations.scanning.scan_after_import', false)) {
            return;
        }

        ScanUsageJob::dispatch();
    }
}
