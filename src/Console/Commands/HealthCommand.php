<?php

declare(strict_types=1);

namespace Syriable\Translations\Console\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Analysis\CompletenessReport;
use Syriable\Translations\Analysis\HealthAnalyzer;
use Syriable\Translations\Analysis\HealthReport;
use Syriable\Translations\Console\Concerns\InteractsWithCatalog;
use Syriable\Translations\Extraction\Extractor;
use Syriable\Translations\Storage\StorageManager;

final class HealthCommand extends Command
{
    use InteractsWithCatalog;

    protected $signature = 'translations:health
        {--strict : Return a non-zero exit code when any issue is found}
        {--json : Output the report as JSON}';

    protected $description = 'Report missing keys, unused keys and per-locale completeness';

    public function handle(Extractor $extractor, StorageManager $storage, HealthAnalyzer $analyzer): int
    {
        $report = $analyzer->analyze(
            $extractor->extract($this->extractionPaths()),
            $this->catalog($storage),
        );

        if ($this->option('json')) {
            $this->line($this->toJson($report));

            return $this->exitCode($report->hasIssues());
        }

        $this->reportKeys('Missing keys (used in code, not in catalog)', $report->missingKeys);
        $this->reportKeys('Unused keys (in catalog, not used in code)', $report->unusedKeys);

        $this->table(
            ['Locale', 'Translated', 'Complete'],
            array_map(static fn (CompletenessReport $c): array => [
                $c->locale,
                $c->translated.'/'.$c->total,
                $c->percentage().'%',
            ], array_values($report->completeness)),
        );

        return $this->exitCode($report->hasIssues());
    }

    /**
     * @param  list<string>  $keys
     */
    private function reportKeys(string $heading, array $keys): void
    {
        $this->info($heading.': '.count($keys));

        foreach (array_slice($keys, 0, 50) as $key) {
            $this->line("  • {$key}");
        }
    }

    private function exitCode(bool $hasIssues): int
    {
        return ($this->option('strict') && $hasIssues) ? self::FAILURE : self::SUCCESS;
    }

    private function toJson(HealthReport $report): string
    {
        $completeness = [];

        foreach ($report->completeness as $code => $c) {
            $completeness[$code] = [
                'total' => $c->total,
                'translated' => $c->translated,
                'percentage' => $c->percentage(),
                'missing' => $c->missingKeys,
            ];
        }

        return (string) json_encode([
            'missing' => $report->missingKeys,
            'unused' => $report->unusedKeys,
            'completeness' => $completeness,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
