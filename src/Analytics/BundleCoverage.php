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

    /**
     * @param  Builder<Bundle>  $query
     * @return Builder<Bundle>
     */
    public function applyProgressCounts(Builder $query): Builder
    {
        $targetLocalesCount = $this->targetLocalesCount();

        return $query->withCount([
            'phrases',
            'phrases as translated_phrases_count' => function (Builder $phraseQuery) use ($targetLocalesCount): void {
                if ($targetLocalesCount === 0) {
                    $phraseQuery->whereRaw('0 = 1');

                    return;
                }

                $phraseQuery->whereHas('messages', function (Builder $messages): void {
                    $messages
                        ->translated()
                        ->whereHas('locale', fn (Builder $locale) => $locale->enabled()->targets());
                }, '=', $targetLocalesCount);
            },
        ]);
    }

    public function percent(Bundle $bundle): float
    {
        $phrasesCount = (int) ($bundle->phrases_count ?? 0);
        $translatedCount = (int) ($bundle->translated_phrases_count ?? 0);

        if ($phrasesCount === 0 || $this->targetLocalesCount() === 0) {
            return 0.0;
        }

        return round($translatedCount / $phrasesCount * 100, 1);
    }

    /**
     * @return list<array{bundle: string, name: string, namespace: ?string, total: int, translated: int, percent: float}>
     */
    public function coverage(?string $bundleName = null): array
    {
        return $this->applyProgressCounts(Bundle::query())
            ->when($bundleName, fn (Builder $query) => $query->where('name', $bundleName))
            ->orderBy('name')
            ->get()
            ->map(fn (Bundle $bundle): array => [
                'bundle' => $bundle->label(),
                'name' => $bundle->name,
                'namespace' => $bundle->namespace,
                'total' => (int) $bundle->phrases_count,
                'translated' => (int) $bundle->translated_phrases_count,
                'percent' => $this->percent($bundle),
            ])
            ->values()
            ->all();
    }
}
