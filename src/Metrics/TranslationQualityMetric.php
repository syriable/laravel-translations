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
use Syriable\Translations\Models\QualityIssue;

class TranslationQualityMetric extends Metric
{
    public function key(): string
    {
        return 'translations.quality';
    }

    public function query(): MetricBuilder
    {
        $localeTable = (new Locale)->getTable();
        $messageTable = (new Message)->getTable();
        $issuesTable = (new QualityIssue)->getTable();

        $weights = config('translations.analytics.quality.weights', []);
        $reviewWeight = (float) ($weights['review'] ?? 0.6);
        $validationWeight = (float) ($weights['validation'] ?? 0.4);
        $sum = $reviewWeight + $validationWeight;

        if ($sum > 0) {
            $reviewWeight /= $sum;
            $validationWeight /= $sum;
        } else {
            $reviewWeight = 0.6;
            $validationWeight = 0.4;
        }

        $reviewWeight = round($reviewWeight, 6);
        $validationWeight = round($validationWeight, 6);

        return Metrics::query(Message::class)
            ->query(fn (Builder $query): Builder => $query
                ->join($localeTable, "{$localeTable}.id", '=', "{$messageTable}.locale_id")
                ->whereRaw("{$localeTable}.enabled = ?", [true])
                ->whereRaw("{$localeTable}.is_source = ?", [false]))
            ->groupBy("{$localeTable}.name")
            ->dataset('translated', fn (DatasetBuilder $dataset): DatasetBuilder => $dataset
                ->count("{$messageTable}.id")
                ->query(fn (Builder $query): Builder => $query->where('status', '!=', MessageStatus::Open->value)))
            ->dataset('approved', fn (DatasetBuilder $dataset): DatasetBuilder => $dataset
                ->count("{$messageTable}.id")
                ->query(fn (Builder $query): Builder => $query->where('status', MessageStatus::Approved->value)))
            ->dataset('issues', fn (DatasetBuilder $dataset): DatasetBuilder => $dataset
                ->countDistinct("{$issuesTable}.id")
                ->query(fn (Builder $query): Builder => $query
                    ->leftJoin($issuesTable, "{$issuesTable}.message_id", '=', "{$messageTable}.id")
                    ->where('status', '!=', MessageStatus::Open->value)))
            ->formula('review', 'approved / translated * 100')
            ->formula('validation', '100 - (issues / translated * 100)')
            ->formula('quality', "(review * {$reviewWeight}) + (validation * {$validationWeight})")
            ->precision(1)
            ->allTime();
    }
}
