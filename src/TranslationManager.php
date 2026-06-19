<?php

namespace Syriable\Translations;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Syriable\Translations\Ai\MachineTranslation;
use Syriable\Translations\Analytics\Insights;
use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Events\LocaleAdded;
use Syriable\Translations\Events\PhraseCreated;
use Syriable\Translations\Exporting\LangExporter;
use Syriable\Translations\Glossary\Glossary;
use Syriable\Translations\Importing\LangImporter;
use Syriable\Translations\Models\Bundle;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Models\Phrase;
use Syriable\Translations\Quality\Inspector;
use Syriable\Translations\Revisions\RevisionRollback;
use Syriable\Translations\Scanning\Loose\LooseStringScanner;
use Syriable\Translations\Scanning\Usage\UsageScanner;
use Syriable\Translations\Support\ExportSummary;
use Syriable\Translations\Support\ImportSummary;
use Syriable\Translations\Support\LocaleMeta;
use Syriable\Translations\Support\MessageSeeder;
use Syriable\Translations\Support\ReviewFlow;

class TranslationManager
{
    public function __construct(
        private readonly LangImporter $importer,
        private readonly LangExporter $exporter,
        private readonly MessageSeeder $seeder,
    ) {}

    public function get(string $key, ?string $locale = null): ?string
    {
        $localeId = $this->localeId($locale);
        $phrase = $this->resolvePhrase($key);

        if ($phrase === null || $localeId === null) {
            return null;
        }

        return optional(
            Message::query()->where('phrase_id', $phrase->id)->where('locale_id', $localeId)->first()
        )->value;
    }

    public function has(string $key, ?string $locale = null): bool
    {
        return $this->get($key, $locale) !== null;
    }

    public function set(string $key, string $value, ?string $locale = null, array $options = []): Message
    {
        return DB::transaction(function () use ($key, $value, $locale, $options): Message {
            $phrase = $this->resolveOrCreatePhrase($key);
            $localeModel = $this->localeModel($locale);

            $message = Message::query()->firstOrNew([
                'phrase_id' => $phrase->id,
                'locale_id' => $localeModel->id,
            ]);

            return Message::withStamp($options['reason'] ?? 'manual', $options['by'] ?? null, $options['meta'] ?? [], function () use ($message, $value, $options): Message {
                $message->fill([
                    'value' => $value,
                    'status' => $options['status'] ?? MessageStatus::Draft,
                    'translated_by' => $options['by'] ?? $message->translated_by,
                ])->save();

                return $message;
            });
        });
    }

    public function forget(string $key, ?string $locale = null): void
    {
        $phrase = $this->resolvePhrase($key);

        if ($phrase === null) {
            return;
        }

        if ($locale === null) {
            $phrase->delete();

            return;
        }

        $localeId = $this->localeId($locale);

        Message::query()
            ->where('phrase_id', $phrase->id)
            ->where('locale_id', $localeId)
            ->update(['value' => null, 'status' => MessageStatus::Open->value]);
    }

    public function all(?string $locale = null): array
    {
        $localeId = $this->localeId($locale);

        return Message::query()
            ->where('locale_id', $localeId)
            ->with('phrase.bundle')
            ->get()
            ->mapWithKeys(fn (Message $message) => [$message->phrase->dottedKey() => $message->value])
            ->all();
    }

    public function locales(): Collection
    {
        return Locale::query()->orderBy('code')->get();
    }

    public function addLocale(string $code, array $attributes = []): Locale
    {
        if (! LocaleMeta::isValidCode($code)) {
            throw new InvalidArgumentException(
                "Invalid locale code [{$code}]. Expected a language code like \"en\", \"pt-BR\" or \"zh-Hans\"."
            );
        }

        $locale = Locale::query()->firstOrCreate(
            ['code' => $code],
            array_merge(
                LocaleMeta::for($code),
                ['enabled' => true],
                $attributes,
            ),
        );

        if ($locale->wasRecentlyCreated) {
            $this->seeder->seedLocale($locale);
            LocaleAdded::dispatch($locale);
        }

        return $locale;
    }

    public function import(array $options = []): ImportSummary
    {
        return $this->importer->import($options);
    }

    public function export(array $options = []): ExportSummary
    {
        return $this->exporter->export($options);
    }

    public function translate(string $key, string $locale, array $options = []): ?Message
    {
        $phrase = $this->resolvePhrase($key);

        if ($phrase === null) {
            return null;
        }

        return $this->ai()->apply($phrase, $this->localeModel($locale), $options);
    }

    public function ai(): MachineTranslation
    {
        return app(MachineTranslation::class);
    }

    public function quality(): Inspector
    {
        return app(Inspector::class);
    }

    public function glossary(): Glossary
    {
        return app(Glossary::class);
    }

    public function insights(): Insights
    {
        return app(Insights::class);
    }

    public function revisions(): RevisionRollback
    {
        return app(RevisionRollback::class);
    }

    public function review(): ReviewFlow
    {
        return app(ReviewFlow::class);
    }

    public function scanUsage(?string $path = null): array
    {
        return app(UsageScanner::class)->scan($path);
    }

    public function scanLoose(?string $path = null): array
    {
        return app(LooseStringScanner::class)->scan($path);
    }

    private function resolvePhrase(string $key): ?Phrase
    {
        [$bundle, $phraseKey] = $this->split($key);

        $phrase = Phrase::query()
            ->whereHas('bundle', fn ($query) => $query->where('name', $bundle)->whereNull('namespace'))
            ->where('key', $phraseKey)
            ->first();

        if ($phrase !== null) {
            return $phrase;
        }

        return Phrase::query()
            ->whereHas('bundle', fn ($query) => $query->where('name', '_json'))
            ->where('key', $key)
            ->first();
    }

    private function resolveOrCreatePhrase(string $key): Phrase
    {
        $existing = $this->resolvePhrase($key);

        if ($existing !== null) {
            return $existing;
        }

        [$bundleName, $phraseKey] = $this->split($key);

        $bundle = Bundle::query()->firstOrCreate(
            ['name' => $bundleName, 'namespace' => null],
            ['format' => 'php'],
        );

        $phrase = $bundle->phrases()->create(['key' => $phraseKey]);

        $this->seeder->seedPhrase($phrase);
        PhraseCreated::dispatch($phrase);

        return $phrase;
    }

    private function split(string $key): array
    {
        if (! str_contains($key, '.')) {
            return ['_json', $key];
        }

        return [Str::before($key, '.'), Str::after($key, '.')];
    }

    private function localeModel(?string $code): Locale
    {
        if ($code === null) {
            $source = Locale::source();

            if ($source !== null) {
                return $source;
            }

            return $this->addLocale(config('translations.source_locale', 'en'), ['is_source' => true]);
        }

        return $this->addLocale($code);
    }

    private function localeId(?string $code): ?int
    {
        if ($code === null) {
            return optional(Locale::source())->id;
        }

        return optional(Locale::query()->where('code', $code)->first())->id;
    }
}
