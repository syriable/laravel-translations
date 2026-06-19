<?php

namespace Syriable\Translations\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\TranslationManager;

class ExportCommand extends Command
{
    protected $signature = 'translations:export {--locale= : Only export this locale} {--bundle= : Only export this bundle}';

    protected $description = 'Export translations from the database back to language files';

    public function handle(TranslationManager $manager): int
    {
        $summary = $manager->export([
            'locale' => $this->option('locale'),
            'bundle' => $this->option('bundle'),
            'source' => 'cli',
        ]);

        $this->components->info(__('translations::messages.export.done', [
            'files' => $summary->fileCount,
            'locales' => $summary->localeCount,
            'duration' => "{$summary->durationMs}ms",
        ]));

        return self::SUCCESS;
    }
}
