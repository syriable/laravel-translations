<?php

declare(strict_types=1);

namespace Syriable\Translations\Console\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Console\Concerns\InteractsWithCatalog;
use Syriable\Translations\Storage\StorageManager;
use Syriable\Translations\Validation\Issue;
use Syriable\Translations\Validation\ValidationPipeline;

final class ValidateCommand extends Command
{
    use InteractsWithCatalog;

    protected $signature = 'translations:validate {--locale= : Limit validation to a single locale}';

    protected $description = 'Validate every translation against its source value';

    public function handle(StorageManager $storage, ValidationPipeline $pipeline): int
    {
        $report = $pipeline->validate($this->catalog($storage), $this->option('locale') ?: null);

        if ($report->isEmpty()) {
            $this->info('All translations passed validation.');

            return self::SUCCESS;
        }

        $this->table(
            ['Locale', 'Severity', 'Key', 'Message'],
            array_map(static fn (Issue $issue): array => [
                $issue->locale,
                $issue->severity->label(),
                $issue->key,
                $issue->message,
            ], $report->issues),
        );

        foreach ($report->countsBySeverity() as $severity => $count) {
            $this->line(ucfirst($severity).": {$count}");
        }

        return $report->hasErrors() ? self::FAILURE : self::SUCCESS;
    }
}
