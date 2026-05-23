<?php

declare(strict_types=1);

namespace Syriable\Translations\Console\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Console\Concerns\InteractsWithCatalog;
use Syriable\Translations\Domain\Catalog;
use Syriable\Translations\Storage\StorageManager;
use Syriable\Translations\Validation\Issue;
use Syriable\Translations\Validation\ValidationIssueRecorder;
use Syriable\Translations\Validation\ValidationPipeline;

final class ValidateCommand extends Command
{
    use InteractsWithCatalog;

    protected $signature = 'translations:validate {--locale= : Limit validation to a single locale}';

    protected $description = 'Validate every translation against its source value';

    public function handle(StorageManager $storage, ValidationPipeline $pipeline, ValidationIssueRecorder $recorder): int
    {
        $catalog = $this->catalog($storage);
        $only = $this->option('locale') ?: null;

        $report = $pipeline->validate($catalog, $only);

        $recorder->recordForLocales($report, $this->validatedLocales($catalog, $only));

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

    /**
     * @return list<string>
     */
    private function validatedLocales(Catalog $catalog, ?string $only): array
    {
        if ($only !== null) {
            return [$only];
        }

        $source = $this->sourceLocale();

        return array_values(array_filter(
            $catalog->localeCodes(),
            static fn (string $code): bool => $code !== $source,
        ));
    }
}
