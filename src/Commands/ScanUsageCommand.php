<?php

namespace Syriable\Translations\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Scanning\Usage\UsageScanner;

class ScanUsageCommand extends Command
{
    protected $signature = 'translations:scan-usage {--path= : Limit the scan to a single path}';

    protected $description = 'Scan source code for translation key usages and record where each key appears';

    public function handle(UsageScanner $scanner): int
    {
        $result = $scanner->scan($this->option('path'));

        $this->components->info("Scanned {$result['files_scanned']} files, recorded {$result['usages_found']} usages.");

        return self::SUCCESS;
    }
}
