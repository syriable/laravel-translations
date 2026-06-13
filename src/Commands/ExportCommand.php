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

        $this->components->info("Exported {$summary->fileCount} files across {$summary->localeCount} locales in {$summary->durationMs}ms.");

        return self::SUCCESS;
    }
}
