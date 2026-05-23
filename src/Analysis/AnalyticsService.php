<?php

declare(strict_types=1);

namespace Syriable\Translations\Analysis;

use Syriable\Translations\Models\ActivityLog;
use Syriable\Translations\Models\AiUsageLog;
use Syriable\Translations\Models\HardcodedString;
use Syriable\Translations\Models\TranslationState;
use Syriable\Translations\Models\ValidationIssue;
use Syriable\Translations\Storage\StorageManager;

/**
 * Aggregates catalog completeness (from lang files) and collaboration metadata
 * (from the database) into a dashboard snapshot. Metadata-backed figures are
 * zero when metadata is disabled.
 */
final class AnalyticsService
{
    public function __construct(
        private readonly StorageManager $storage,
        private readonly string $sourceLocale,
    ) {}

    public function overview(): DashboardOverview
    {
        $completeness = $this->completeness();
        $metadata = config('translations.metadata.enabled', true) === true;

        return new DashboardOverview(
            $completeness,
            $completeness[0]->total ?? 0,
            $metadata ? $this->issuesBySeverity() : [],
            $metadata ? TranslationState::query()->pending()->count() : 0,
            $metadata ? HardcodedString::query()->pending()->count() : 0,
            $metadata ? AiUsageLog::query()->count() : 0,
            $metadata ? (int) AiUsageLog::query()->sum('output_characters') : 0,
            $metadata ? ActivityLog::query()->count() : 0,
        );
    }

    /**
     * Per-locale completeness measured against the source locale's keys.
     *
     * @return list<CompletenessReport>
     */
    public function completeness(): array
    {
        $catalog = $this->storage->driver()->readAll()->withSource($this->sourceLocale);
        $source = $catalog->source();

        if ($source === null) {
            return [];
        }

        $sourceKeys = $source->keys();
        $total = count($sourceKeys);

        $reports = [];

        foreach ($catalog->all() as $code => $localeCatalog) {
            $translated = 0;
            $missing = [];

            foreach ($sourceKeys as $key) {
                $value = $localeCatalog->get($key);

                if ($localeCatalog->has($key) && $value !== null && $value !== '') {
                    $translated++;
                } else {
                    $missing[] = $key;
                }
            }

            $reports[] = new CompletenessReport($code, $total, $translated, $missing);
        }

        return $reports;
    }

    /**
     * @return array<string, int>
     */
    private function issuesBySeverity(): array
    {
        return ValidationIssue::query()
            ->selectRaw('severity, count(*) as aggregate')
            ->groupBy('severity')
            ->pluck('aggregate', 'severity')
            ->map(static fn (int|string $count): int => (int) $count)
            ->all();
    }
}
