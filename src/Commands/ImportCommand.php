<?php

namespace Syriable\Translations\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\TranslationManager;

class ImportCommand extends Command
{
    protected $signature = 'translations:import {--fresh : Delete existing translations first} {--no-overwrite : Keep existing values when a key already has one}';

    protected $description = 'Import language files from disk into the database';

    public function handle(TranslationManager $manager): int
    {
        $summary = $manager->import([
            'fresh' => (bool) $this->option('fresh'),
            'overwrite' => ! $this->option('no-overwrite'),
            'source' => 'cli',
        ]);

        $this->components->info(__('translations::messages.import.done'));
        $this->table(
            [
                __('translations::messages.import.table.locales'),
                __('translations::messages.import.table.phrases'),
                __('translations::messages.import.table.created'),
                __('translations::messages.import.table.updated'),
                __('translations::messages.import.table.duration'),
            ],
            [[$summary->localeCount, $summary->phraseCount, $summary->createdCount, $summary->updatedCount, "{$summary->durationMs}ms"]],
        );

        return self::SUCCESS;
    }
}
