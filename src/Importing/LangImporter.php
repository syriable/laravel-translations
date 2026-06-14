<?php

namespace Syriable\Translations\Importing;

use Illuminate\Support\Facades\DB;
use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Events\ImportFinished;
use Syriable\Translations\Files\LangReader;
use Syriable\Translations\Models\Bundle;
use Syriable\Translations\Models\ImportRecord;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Models\Phrase;
use Syriable\Translations\Support\ImportSummary;
use Syriable\Translations\Support\LocaleMeta;
use Syriable\Translations\Support\MessageSeeder;
use Syriable\Translations\Support\PlaceholderScanner;

class LangImporter
{
    public function __construct(
        private readonly LangReader $reader,
        private readonly PlaceholderScanner $scanner,
        private readonly MessageSeeder $seeder,
    ) {}

    public function import(array $options = []): ImportSummary
    {
        $fresh = (bool) ($options['fresh'] ?? false);
        $overwrite = (bool) ($options['overwrite'] ?? true);
        $langPath = $options['lang_path'] ?? config('translations.lang_path');

        $started = hrtime(true);
        $summary = new ImportSummary;

        DB::transaction(fn () => Message::withoutEvents(
            fn () => $this->populate($fresh, $overwrite, $langPath, $summary),
        ));

        $summary->phraseCount = Phrase::query()->count();
        $summary->durationMs = (int) ((hrtime(true) - $started) / 1_000_000);

        $this->record($summary, $options, $fresh);

        ImportFinished::dispatch($summary);

        return $summary;
    }

    private function populate(bool $fresh, bool $overwrite, string $langPath, ImportSummary $summary): void
    {
        if ($fresh) {
            $this->clear();
        }

        $this->ensureSource();

        foreach ($this->reader->locales($langPath) as $code) {
            $locale = $this->ensureLocale($code);
            $summary->localeCount++;

            foreach ($this->reader->phpFiles($langPath, $code) as $group => $path) {
                if ($this->excluded($group)) {
                    continue;
                }

                $bundle = $this->bundle($group, null, "{$group}.php", 'php');
                $this->importValues($bundle, $locale, $this->reader->readPhp($path), $overwrite, $summary);
            }

            $jsonPath = $this->reader->jsonPath($langPath, $code);

            if (is_file($jsonPath)) {
                $bundle = $this->bundle('_json', null, $jsonPath, 'json');
                $this->importValues($bundle, $locale, $this->reader->readJson($jsonPath), $overwrite, $summary);
            }
        }

        if (config('translations.import.scan_vendor', true)) {
            $this->importVendor($langPath, $overwrite, $summary);
        }

        $summary->createdCount += $this->seeder->seedAll();
    }

    private function importVendor(string $langPath, bool $overwrite, ImportSummary $summary): void
    {
        foreach ($this->reader->vendorFiles($langPath) as $entry) {
            $locale = $this->ensureLocale($entry['locale']);
            $bundle = $this->bundle($entry['group'], $entry['namespace'], $entry['path'], 'php');
            $this->importValues($bundle, $locale, $this->reader->readPhp($entry['path']), $overwrite, $summary);
        }
    }

    private function importValues(Bundle $bundle, Locale $locale, array $values, bool $overwrite, ImportSummary $summary): void
    {
        foreach ($values as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $value = (string) $value;
            $phrase = $this->phrase($bundle, (string) $key, $value);

            $message = Message::query()->firstOrNew([
                'phrase_id' => $phrase->id,
                'locale_id' => $locale->id,
            ]);

            if ($message->exists && ! $overwrite && filled($message->value)) {
                continue;
            }

            $created = ! $message->exists;
            $message->value = $value;
            $message->status = MessageStatus::Draft;
            $message->save();

            $created ? $summary->createdCount++ : $summary->updatedCount++;
        }
    }

    private function phrase(Bundle $bundle, string $key, string $sourceValue): Phrase
    {
        return Phrase::query()->firstOrCreate(
            ['bundle_id' => $bundle->id, 'key' => $key],
            [
                'placeholders' => config('translations.import.detect_placeholders', true) ? $this->scanner->placeholders($sourceValue) : [],
                'is_html' => config('translations.import.detect_html', true) && $this->scanner->hasHtml($sourceValue),
                'is_plural' => config('translations.import.detect_plural', true) && $this->scanner->isPlural($sourceValue),
            ],
        );
    }

    private function bundle(string $name, ?string $namespace, string $path, string $format): Bundle
    {
        return Bundle::query()->firstOrCreate(
            ['name' => $name, 'namespace' => $namespace],
            ['file_path' => $path, 'format' => $format],
        );
    }

    private function ensureSource(): Locale
    {
        return $this->ensureLocale(config('translations.source_locale', 'en'), true);
    }

    private function ensureLocale(string $code, bool $source = false): Locale
    {
        $locale = Locale::query()->firstOrCreate(
            ['code' => $code],
            LocaleMeta::for($code) + ['enabled' => true],
        );

        if ($source && ! $locale->is_source) {
            $locale->update(['is_source' => true, 'enabled' => true]);
            Locale::flushSourceCache();
        }

        return $locale;
    }

    private function excluded(string $group): bool
    {
        $excluded = array_map(
            fn (string $file) => pathinfo($file, PATHINFO_FILENAME),
            config('translations.import.exclude_files', []),
        );

        return in_array(basename($group), $excluded, true);
    }

    private function clear(): void
    {
        Message::query()->delete();
        Phrase::query()->delete();
        Bundle::query()->delete();
    }

    private function record(ImportSummary $summary, array $options, bool $fresh): void
    {
        ImportRecord::query()->create($summary->toArray() + [
            'source' => $options['source'] ?? 'cli',
            'triggered_by' => $options['triggered_by'] ?? null,
            'fresh' => $fresh,
        ]);
    }
}
