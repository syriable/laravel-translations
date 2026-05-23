<?php

declare(strict_types=1);

namespace Syriable\Translations\Console\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Console\Concerns\InteractsWithCatalog;
use Syriable\Translations\Detection\HardcodedStringDetector;
use Syriable\Translations\Detection\HardcodedStringRecorder;

final class DetectHardcodedCommand extends Command
{
    use InteractsWithCatalog;

    protected $signature = 'translations:detect-hardcoded';

    protected $description = 'Scan source code for hardcoded strings that should be translated';

    public function handle(HardcodedStringDetector $detector, HardcodedStringRecorder $recorder): int
    {
        if (! $recorder->enabled()) {
            $this->warn('Translation metadata is disabled; nothing to record.');

            return self::SUCCESS;
        }

        $found = $detector->detect($this->extractionPaths());
        $recorded = $recorder->sync($found);

        $this->info("Found {$recorded} hardcoded string(s) to review.");

        return self::SUCCESS;
    }
}
