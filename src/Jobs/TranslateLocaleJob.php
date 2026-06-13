<?php

namespace Syriable\Translations\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Syriable\Translations\Ai\MachineTranslation;
use Syriable\Translations\Models\Locale;

class TranslateLocaleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        public int $localeId,
        public array $options = [],
    ) {
        $this->onConnection(config('translations.queue.connection'));
        $this->onQueue(config('translations.queue.name', 'translations'));
    }

    public function handle(MachineTranslation $machine): void
    {
        $locale = Locale::query()->find($this->localeId);

        if ($locale !== null) {
            $machine->translateOpen($locale, $this->options);
        }
    }
}
