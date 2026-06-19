<?php

namespace Syriable\Translations\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Jobs\ScanLooseJob;
use Syriable\Translations\Scanning\Loose\LooseStringScanner;

class ScanLooseCommand extends Command
{
    protected $signature = 'translations:scan-loose {--path= : Limit the scan to a single path} {--queue : Dispatch the scan to the queue instead of running inline}';

    protected $description = 'Detect hardcoded, untranslated strings in your source code';

    public function handle(LooseStringScanner $scanner): int
    {
        if ($this->option('queue')) {
            ScanLooseJob::dispatch($this->option('path'));
            $this->components->info(__('translations::messages.scan_loose.queued'));

            return self::SUCCESS;
        }

        $result = $scanner->scan($this->option('path'));

        $this->components->info(__('translations::messages.scan_loose.done', [
            'files' => $result['files_scanned'],
            'detected' => $result['detected'],
        ]));

        return self::SUCCESS;
    }
}
