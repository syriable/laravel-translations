<?php

namespace Syriable\Translations\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\TranslationManager;

class InstallCommand extends Command
{
    protected $signature = 'translations:install {--import : Import existing language files after installing}';

    protected $description = 'Publish the config, run the migrations, and optionally import your language files';

    public function handle(TranslationManager $manager): int
    {
        $this->components->info(__('translations::messages.install.publishing'));
        $this->callSilently('vendor:publish', ['--tag' => 'translations-config']);

        $this->components->info(__('translations::messages.install.migrating'));
        $this->call('migrate');

        if ($this->option('import')) {
            $this->components->info(__('translations::messages.install.importing'));
            $summary = $manager->import(['source' => 'cli']);
            $this->components->info(__('translations::messages.install.imported', [
                'phrases' => $summary->phraseCount,
                'locales' => $summary->localeCount,
            ]));
        }

        $this->components->info(__('translations::messages.install.done'));

        return self::SUCCESS;
    }
}
