<?php

namespace Syriable\Translations\Analytics;

use Illuminate\Database\Eloquent\Builder;
use Syriable\Translations\Models\Bundle;
use Syriable\Translations\Models\Locale;

class BundleCoverage
{
    public function targetLocalesCount(): int
    {
        return Locale::query()->enabled()->targets()->count();
    }

    public function applyProgressCounts(Builder $query): Builder
    {
        $targets = $this->targetLocalesCount();

        return $query->withCount([
            'phrases',
            'phrases as translated_phrases_count' => function (Builder $phrases) use ($targets): void {
                if ($targets === 0) {
                    $phrases->whereRaw('1 = 0');

                    return;
                }

                $phrases->whereHas('messages', function (Builder $messages): void {
                    $messages->translated()->whereHas('locale', fn (Builder $locale) => $locale->enabled()->targets());
                }, '=', $targets);
            },
        ]);
    }

    public function percent(Bundle $bundle): float
    {
        $total = (int) ($bundle->phrases_count ?? 0);

        if ($total === 0) {
            return 0.0;
        }

        return round((int) ($bundle->translated_phrases_count ?? 0) / $total * 100, 1);
    }

    public function coverage(?string $bundleName = null): array
    {
        return Bundle::query()
            ->when($bundleName, fn (Builder $query) => $query->where('name', $bundleName))
            ->tap(fn (Builder $query) => $this->applyProgressCounts($query))
            ->orderBy('namespace')
            ->orderBy('name')
            ->get()
            ->map(fn (Bundle $bundle) => [
                'bundle' => $bundle->label(),
                'name' => $bundle->name,
                'namespace' => $bundle->namespace,
                'total' => (int) $bundle->phrases_count,
                'translated' => (int) $bundle->translated_phrases_count,
                'percent' => $this->percent($bundle),
            ])
            ->all();
    }
}
