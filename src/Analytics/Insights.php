<?php

namespace Syriable\Translations\Analytics;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Models\Revision;

class Insights
{
    public function dashboard(): array
    {
        return Cache::remember('translations.insights', config('translations.analytics.cache_ttl', 3600), fn () => [
            'coverage' => $this->coverage(),
            'overall_coverage' => $this->overallCoverage(),
            'bundle_coverage' => $this->bundleCoverage(),
            'leaderboard' => $this->leaderboard(),
            'velocity' => $this->velocity(),
            'stale' => $this->staleCounts(),
        ]);
    }

    public function bundleCoverage(?string $bundleName = null): array
    {
        return app(BundleCoverage::class)->coverage($bundleName);
    }

    public function coverage(): array
    {
        $stats = Message::query()
            ->selectRaw('locale_id, count(*) as total')
            ->selectRaw('sum(case when status != ? then 1 else 0 end) as translated', [MessageStatus::Open->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as approved', [MessageStatus::Approved->value])
            ->groupBy('locale_id')
            ->get()
            ->keyBy('locale_id');

        return Locale::query()->enabled()->targets()->get()->map(function (Locale $locale) use ($stats): array {
            $row = $stats->get($locale->id);
            $total = (int) ($row->total ?? 0);
            $translated = (int) ($row->translated ?? 0);

            return [
                'locale' => $locale->code,
                'total' => $total,
                'translated' => $translated,
                'approved' => (int) ($row->approved ?? 0),
                'percent' => $total > 0 ? round($translated / $total * 100, 1) : 0.0,
            ];
        })->values()->all();
    }

    public function overallCoverage(): float
    {
        $total = Message::query()->whereHas('locale', fn (Builder $query): Builder => $query->where('is_source', false))->count();

        if ($total === 0) {
            return 0.0;
        }

        $done = Message::query()->translated()->whereHas('locale', fn (Builder $query): Builder => $query->where('is_source', false))->count();

        return round($done / $total * 100, 1);
    }

    public function leaderboard(): array
    {
        return Revision::query()
            ->whereNotNull('changed_by')
            ->selectRaw('changed_by, count(*) as changes')
            ->groupBy('changed_by')
            ->orderByDesc('changes')
            ->limit(config('translations.analytics.leaderboard_limit', 10))
            ->get()
            ->map(fn (Revision $row): array => ['member' => $row->changed_by, 'changes' => (int) $row->getAttribute('changes')])
            ->all();
    }

    public function velocity(int $days = 30): array
    {
        $since = now()->subDays($days);

        return Revision::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('date(created_at) as day, count(*) as changes')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn (Revision $row): array => ['day' => (string) $row->getAttribute('day'), 'changes' => (int) $row->getAttribute('changes')])
            ->all();
    }

    public function stale(?int $localeId = null): array
    {
        $staleAfter = now()->subDays(config('translations.analytics.stale_after_days', 30));

        return Message::query()
            ->translated()
            ->where('updated_at', '<', $staleAfter)
            ->when($localeId, fn (Builder $query): Builder => $query->where('locale_id', $localeId))
            ->with(['phrase', 'locale'])
            ->get()
            ->all();
    }

    public function staleCounts(): array
    {
        $staleAfter = now()->subDays(config('translations.analytics.stale_after_days', 30));

        return Message::query()
            ->translated()
            ->where('updated_at', '<', $staleAfter)
            ->selectRaw('locale_id, count(*) as total')
            ->groupBy('locale_id')
            ->pluck('total', 'locale_id')
            ->all();
    }

    public function flush(): void
    {
        Cache::forget('translations.insights');
    }
}
