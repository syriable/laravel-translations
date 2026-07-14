<?php

declare(strict_types=1);

namespace Syriable\Translations\Metrics;

use Illuminate\Database\Eloquent\Builder;
use Syriable\Metrics\Builder\DatasetBuilder;
use Syriable\Metrics\Builder\MetricBuilder;
use Syriable\Metrics\Facades\Metrics;
use Syriable\Metrics\Metric;
use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Message;

class TranslationCoverageMetric extends Metric
{
    public function key(): string
    {
        return 'translations.coverage';
    }

    public function query(): MetricBuilder
    {
        $localeTable = (new Locale)->getTable();
        $messageTable = (new Message)->getTable();

        return Metrics::query(Message::class)
            ->query(fn (Builder $query): Builder => $query
                ->join($localeTable, "{$localeTable}.id", '=', "{$messageTable}.locale_id")
                ->whereRaw("{$localeTable}.enabled = ?", [true])
                ->whereRaw("{$localeTable}.is_source = ?", [false]))
            ->groupBy("{$localeTable}.name")
            ->dataset('total', fn (DatasetBuilder $dataset): DatasetBuilder => $dataset->count("{$messageTable}.id"))
            ->dataset('translated', fn (DatasetBuilder $dataset): DatasetBuilder => $dataset
                ->count("{$messageTable}.id")
                ->query(fn (Builder $query): Builder => $query->where('status', '!=', MessageStatus::Open->value)))
            ->dataset('untranslated', fn (DatasetBuilder $dataset): DatasetBuilder => $dataset
                ->count("{$messageTable}.id")
                ->query(fn (Builder $query): Builder => $query->where('status', MessageStatus::Open->value)))
            ->dataset('approved', fn (DatasetBuilder $dataset): DatasetBuilder => $dataset
                ->count("{$messageTable}.id")
                ->query(fn (Builder $query): Builder => $query->where('status', MessageStatus::Approved->value)))
            ->formula('coverage', 'translated / total * 100')
            ->precision(1)
            ->allTime();
    }
}
