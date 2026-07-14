<?php

declare(strict_types=1);

namespace Syriable\Translations\Metrics;

use Illuminate\Database\Eloquent\Builder;
use Syriable\Metrics\Builder\DatasetBuilder;
use Syriable\Metrics\Builder\MetricBuilder;
use Syriable\Metrics\Facades\Metrics;
use Syriable\Metrics\Metric;
use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Models\Bundle;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Models\Phrase;

class BundleCoverageMetric extends Metric
{
    public function key(): string
    {
        return 'translations.bundle_coverage';
    }

    public function query(): MetricBuilder
    {
        $bundleTable = (new Bundle)->getTable();
        $localeTable = (new Locale)->getTable();
        $messageTable = (new Message)->getTable();
        $phraseTable = (new Phrase)->getTable();

        $targetsCount = Locale::query()
            ->enabled()
            ->targets()
            ->selectRaw('count(*)');

        $phraseProgress = Phrase::query()
            ->join($bundleTable, "{$bundleTable}.id", '=', "{$phraseTable}.bundle_id")
            ->leftJoin($messageTable, "{$messageTable}.phrase_id", '=', "{$phraseTable}.id")
            ->leftJoin($localeTable, "{$localeTable}.id", '=', "{$messageTable}.locale_id")
            ->where(function (Builder $query) use ($localeTable): void {
                $query
                    ->whereNull("{$localeTable}.id")
                    ->orWhere(function (Builder $sub) use ($localeTable): void {
                        $sub
                            ->whereRaw("{$localeTable}.enabled = ?", [true])
                            ->whereRaw("{$localeTable}.is_source = ?", [false]);
                    });
            })
            ->groupBy("{$phraseTable}.id", "{$bundleTable}.namespace", "{$bundleTable}.name")
            ->selectRaw("{$phraseTable}.id as phrase_id")
            ->selectRaw("{$bundleTable}.namespace as bundle_namespace")
            ->selectRaw("{$bundleTable}.name as bundle_name")
            ->selectRaw(
                "count(distinct case when {$messageTable}.status != ? and {$localeTable}.id is not null then {$messageTable}.locale_id end) as translated_locales",
                [MessageStatus::Open->value],
            )
            ->selectSub($targetsCount, 'target_locales')
            ->selectRaw(
                'case when coalesce(target_locales, 0) > 0 and translated_locales = target_locales then 1 else 0 end as is_complete',
            )
            ->selectRaw(
                "case when {$bundleTable}.namespace is null then {$bundleTable}.name else concat({$bundleTable}.namespace, '::', {$bundleTable}.name) end as bundle_label",
            );

        return Metrics::query(fn (): Builder => Phrase::query()->fromSub($phraseProgress->toBase(), 'phrase_progress'))
            ->groupBy('bundle_label')
            ->dataset('total_phrases', fn (DatasetBuilder $dataset): DatasetBuilder => $dataset->count('phrase_id'))
            ->dataset('completed_phrases', fn (DatasetBuilder $dataset): DatasetBuilder => $dataset->sum('is_complete'))
            ->formula('percent', 'completed_phrases / total_phrases * 100')
            ->precision(1)
            ->allTime();
    }
}
