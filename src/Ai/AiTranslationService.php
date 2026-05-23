<?php

declare(strict_types=1);

namespace Syriable\Translations\Ai;

use Syriable\Translations\Contracts\Translator;
use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Glossary\GlossaryService;
use Syriable\Translations\Management\CatalogManager;
use Syriable\Translations\Models\AiUsageLog;
use Syriable\Translations\Storage\StorageManager;

/**
 * Fills missing target translations using the configured translator. Source of
 * truth stays in lang files: each result is written through the CatalogManager
 * (so revisions, validation and activity all fire), and provider usage is
 * logged for analytics.
 */
final class AiTranslationService
{
    public function __construct(
        private readonly Translator $translator,
        private readonly GlossaryService $glossary,
        private readonly CatalogManager $catalog,
        private readonly StorageManager $storage,
    ) {}

    public function available(): bool
    {
        return config('translations.ai.enabled', false) === true && $this->translator->available();
    }

    /**
     * Translate the missing/empty target values for a locale.
     *
     * @param  list<string>|null  $only  restrict to these keys
     */
    public function translateMissing(string $targetLocale, ?array $only = null): AiTranslationResult
    {
        $sourceLocale = (string) config('translations.locales.source', 'en');

        if ($targetLocale === $sourceLocale) {
            return new AiTranslationResult(0, 0);
        }

        $pending = $this->pendingStrings($sourceLocale, $targetLocale, $only);

        if ($pending === []) {
            return new AiTranslationResult(0, 0);
        }

        $translated = $this->translator->translate(
            $pending,
            $sourceLocale,
            $targetLocale,
            $this->glossary->forLocale($targetLocale),
        );

        foreach ($translated as $key => $value) {
            $this->catalog->set($targetLocale, $key, $value);
        }

        $this->logUsage($sourceLocale, $targetLocale, $pending, $translated);

        return new AiTranslationResult(count($translated), count($pending) - count($translated));
    }

    /**
     * @param  list<string>|null  $only
     * @return array<string, string>
     */
    private function pendingStrings(string $sourceLocale, string $targetLocale, ?array $only): array
    {
        $source = $this->storage->driver()->read(new Locale($sourceLocale));
        $target = $this->storage->driver()->read(new Locale($targetLocale));

        $pending = [];

        foreach ($source->keys() as $key) {
            $sourceValue = $source->get($key);

            if ($sourceValue === null || $sourceValue === '') {
                continue;
            }

            if ($only !== null && ! in_array($key, $only, true)) {
                continue;
            }

            $targetValue = $target->get($key);

            if ($targetValue !== null && $targetValue !== '') {
                continue;
            }

            $pending[$key] = $sourceValue;
        }

        return $pending;
    }

    /**
     * @param  array<string, string>  $pending
     * @param  array<string, string>  $translated
     */
    private function logUsage(string $sourceLocale, string $targetLocale, array $pending, array $translated): void
    {
        if (config('translations.metadata.enabled', true) !== true) {
            return;
        }

        AiUsageLog::create([
            'provider' => $this->translator->name(),
            'model' => $this->translator->model(),
            'source_locale' => $sourceLocale,
            'target_locale' => $targetLocale,
            'keys' => count($translated),
            'input_characters' => $this->characters(array_intersect_key($pending, $translated)),
            'output_characters' => $this->characters($translated),
            'success' => true,
        ]);
    }

    /**
     * @param  array<string, string>  $strings
     */
    private function characters(array $strings): int
    {
        return array_sum(array_map('mb_strlen', $strings));
    }
}
