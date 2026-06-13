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
        $this->components->info('Publishing configuration...');
        $this->callSilently('vendor:publish', ['--tag' => 'translations-config']);

        $this->components->info('Running migrations...');
        $this->call('migrate');

        if ($this->option('import')) {
            $this->components->info('Importing language files...');
            $summary = $manager->import(['source' => 'cli']);
            $this->components->info("Imported {$summary->phraseCount} phrases across {$summary->localeCount} locales.");
        }

        $this->components->info('Translations installed.');

        return self::SUCCESS;
    }
}
