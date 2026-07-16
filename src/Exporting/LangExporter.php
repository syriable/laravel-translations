<?php

namespace Syriable\Translations\Exporting;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Events\ExportFinished;
use Syriable\Translations\Files\LangWriter;
use Syriable\Translations\Models\Bundle;
use Syriable\Translations\Models\ExportRecord;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Phrase;
use Syriable\Translations\Support\ExportSummary;

class LangExporter
{
    public function __construct(
        private readonly LangWriter $writer,
    ) {}

    public function export(array $options = []): ExportSummary
    {
        $langPath = $options['lang_path'] ?? config('translations.lang_path');
        $localeFilter = $options['locale'] ?? null;
        $bundleFilter = $options['bundle'] ?? null;

        $started = hrtime(true);
        $summary = new ExportSummary;

        $locales = Locale::query()
            ->enabled()
            ->when($localeFilter, fn (Builder $query): Builder => $query->where('code', $localeFilter))
            ->get();

        foreach ($locales as $locale) {
            $summary->localeCount++;
            $this->exportLocale($locale, $langPath, $bundleFilter, $summary);
        }

        $summary->durationMs = (int) ((hrtime(true) - $started) / 1_000_000);

        ExportRecord::query()->create([
            'locale_count' => $summary->localeCount,
            'file_count' => $summary->fileCount,
            'phrase_count' => $summary->phraseCount,
            'duration_ms' => $summary->durationMs,
            'source' => $options['source'] ?? 'cli',
            'triggered_by' => $options['triggered_by'] ?? null,
        ]);

        ExportFinished::dispatch($summary);

        return $summary;
    }

    private function exportLocale(Locale $locale, string $langPath, ?string $bundleFilter, ExportSummary $summary): void
    {
        $bundles = Bundle::query()
            ->when($bundleFilter, fn (Builder $query): Builder => $query->where('name', $bundleFilter))
            ->get();

        foreach ($bundles as $bundle) {
            $values = $this->collect($bundle, $locale);

            if ($values === []) {
                continue;
            }

            $path = $this->path($langPath, $locale->code, $bundle);
            $sort = config('translations.export.sort_keys', true);

            $written = $bundle->isJson()
                ? $this->writer->writeJson($path, $values, $sort)
                : $this->writer->writePhp($path, $values, $sort);

            if ($written) {
                $summary->fileCount++;
                $summary->phraseCount += count($values);
            }
        }
    }

    private function collect(Bundle $bundle, Locale $locale): array
    {
        $approvedOnly = config('translations.export.approved_only', false);
        $excludeEmpty = config('translations.export.exclude_empty', true);

        $messages = Phrase::query()
            ->where('bundle_id', $bundle->id)
            ->with(['messages' => fn (Relation $query): Relation => $query->where('locale_id', $locale->id)])
            ->get();

        $values = [];

        foreach ($messages as $phrase) {
            $message = $phrase->messages->first();

            if ($message === null) {
                continue;
            }

            if ($excludeEmpty && blank($message->value)) {
                continue;
            }

            if ($approvedOnly && $message->status !== MessageStatus::Approved) {
                continue;
            }

            $values[$phrase->key] = $message->value;
        }

        return $values;
    }

    private function path(string $langPath, string $code, Bundle $bundle): string
    {
        if ($bundle->isJson()) {
            return "{$langPath}/{$code}.json";
        }

        if ($bundle->namespace) {
            return "{$langPath}/vendor/{$bundle->namespace}/{$code}/{$bundle->name}.php";
        }

        $relative = $bundle->file_path ?: "{$bundle->name}.php";

        return "{$langPath}/{$code}/{$relative}";
    }
}
