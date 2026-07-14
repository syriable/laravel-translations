<?php

declare(strict_types=1);

namespace Syriable\Translations\Metrics;

use Illuminate\Database\Eloquent\Builder;
use Syriable\Metrics\Builder\DatasetBuilder;
use Syriable\Metrics\Builder\MetricBuilder;
use Syriable\Metrics\Facades\Metrics;
use Syriable\Metrics\Metric;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Models\Revision;

class TranslationVelocityMetric extends Metric
{
    public function key(): string
    {
        return 'translations.velocity';
    }

    public function query(): MetricBuilder
    {
        $localeTable = (new Locale)->getTable();
        $messageTable = (new Message)->getTable();
        $revisionTable = (new Revision)->getTable();

        return Metrics::query(Revision::class)
            ->query(fn (Builder $query): Builder => $query
                ->join($messageTable, "{$messageTable}.id", '=', "{$revisionTable}.message_id")
                ->join($localeTable, "{$localeTable}.id", '=', "{$messageTable}.locale_id")
                ->whereRaw("{$localeTable}.enabled = ?", [true])
                ->whereRaw("{$localeTable}.is_source = ?", [false]))
            ->dateColumn("{$revisionTable}.created_at")
            ->dataset('changes', fn (DatasetBuilder $dataset): DatasetBuilder => $dataset->count("{$revisionTable}.id"));
    }
}
