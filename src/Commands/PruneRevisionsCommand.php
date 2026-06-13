<?php

namespace Syriable\Translations\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Models\Revision;

class PruneRevisionsCommand extends Command
{
    protected $signature = 'translations:prune-revisions {--days= : Override the configured retention window} {--dry-run : Report what would be deleted without deleting}';

    protected $description = 'Delete revision history older than the retention window, keeping the latest revision per message';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('translations.revisions.retention_days', 90));
        $cutoff = now()->subDays($days);

        $latestIds = Revision::query()
            ->selectRaw('max(id) as id')
            ->groupBy('message_id')
            ->pluck('id');

        $query = Revision::query()
            ->where('created_at', '<', $cutoff)
            ->whereNotIn('id', $latestIds);

        if ($this->option('dry-run')) {
            $this->components->info("{$query->count()} revisions older than {$days} days would be pruned.");

            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->components->info("Pruned {$deleted} revisions older than {$days} days.");

        return self::SUCCESS;
    }
}
