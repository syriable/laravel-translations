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
            $this->components->info('Usage scan dispatched to the queue.');

            return self::SUCCESS;
        }

        $result = $scanner->scan($this->option('path'));

        $this->components->info("Scanned {$result['files_scanned']} files, recorded {$result['usages_found']} usages.");

        return self::SUCCESS;
    }
}
