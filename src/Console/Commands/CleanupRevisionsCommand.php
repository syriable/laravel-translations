<?php

declare(strict_types=1);

namespace Syriable\Translations\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Syriable\Translations\Models\TranslationRevision;

final class CleanupRevisionsCommand extends Command
{
    protected $signature = 'translations:revisions:cleanup {--days= : Delete revisions older than this many days (defaults to config)}';

    protected $description = 'Prune old translation revisions to keep the history table lean';

    public function handle(): int
    {
        if (config('translations.metadata.enabled', true) !== true) {
            $this->warn('Translation metadata is disabled; nothing to clean up.');

            return self::SUCCESS;
        }

        $days = $this->resolveDays();

        if ($days <= 0) {
            $this->info('Revision pruning is disabled (retention set to keep forever).');

            return self::SUCCESS;
        }

        $cutoff = Carbon::now()->subDays($days);

        $deleted = TranslationRevision::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} revision(s) older than {$days} day(s).");

        return self::SUCCESS;
    }

    private function resolveDays(): int
    {
        $option = $this->option('days');

        if ($option !== null && is_numeric($option)) {
            return (int) $option;
        }

        return (int) config('translations.metadata.revisions.prune_after_days', 90);
    }
}
