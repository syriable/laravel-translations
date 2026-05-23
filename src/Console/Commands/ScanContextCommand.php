<?php

declare(strict_types=1);

namespace Syriable\Translations\Console\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Console\Concerns\InteractsWithCatalog;
use Syriable\Translations\Extraction\Extractor;
use Syriable\Translations\Extraction\KeyContextRecorder;

final class ScanContextCommand extends Command
{
    use InteractsWithCatalog;

    protected $signature = 'translations:scan-context';

    protected $description = 'Scan source code and record where each translation key is used';

    public function handle(Extractor $extractor, KeyContextRecorder $recorder): int
    {
        if (! $recorder->enabled()) {
            $this->warn('Translation metadata is disabled; nothing to record.');

            return self::SUCCESS;
        }

        $result = $extractor->extract($this->extractionPaths());
        $recorded = $recorder->sync($result);

        $this->info("Recorded {$recorded} usage(s) across {$result->count()} key(s).");

        return self::SUCCESS;
    }
}
