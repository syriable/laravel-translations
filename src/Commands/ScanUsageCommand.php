<?php

namespace Syriable\Translations\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Jobs\ScanUsageJob;
use Syriable\Translations\Scanning\Usage\UsageScanner;

class ScanUsageCommand extends Command
{
    protected $signature = 'translations:scan-usage {--path= : Limit the scan to a single path} {--queue : Dispatch the scan to the queue instead of running inline}';

    protected $description = 'Scan source code for translation key usages and record where each key appears';

    public function handle(UsageScanner $scanner): int
    {
        if ($this->option('queue')) {
            ScanUsageJob::dispatch($this->option('path'));
            $this->components->info(__('translations::messages.scan_usage.queued'));

            return self::SUCCESS;
        }

        $result = $scanner->scan($this->option('path'));

        $this->components->info(__('translations::messages.scan_usage.done', [
            'files' => $result['files_scanned'],
            'usages' => $result['usages_found'],
        ]));

        return self::SUCCESS;
    }
}
