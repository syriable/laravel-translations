<?php

namespace Syriable\Translations\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Syriable\Translations\Scanning\Usage\UsageScanner;

class ScanUsageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        public ?string $path = null,
    ) {
        $this->onConnection(config('translations.queue.connection'));
        $this->onQueue(config('translations.queue.name', 'translations'));
    }

    public function handle(UsageScanner $scanner): void
    {
        $scanner->scan($this->path);
    }
}
