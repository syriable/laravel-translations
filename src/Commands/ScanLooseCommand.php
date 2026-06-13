<?php

namespace Syriable\Translations\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Scanning\Loose\LooseStringScanner;

class ScanLooseCommand extends Command
{
    protected $signature = 'translations:scan-loose {--path= : Limit the scan to a single path}';

    protected $description = 'Detect hardcoded, untranslated strings in your source code';

    public function handle(LooseStringScanner $scanner): int
    {
        $result = $scanner->scan($this->option('path'));

        $this->components->info("Scanned {$result['files_scanned']} files, found {$result['detected']} hardcoded strings.");

        return self::SUCCESS;
    }
}
