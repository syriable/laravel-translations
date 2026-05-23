<?php

declare(strict_types=1);

namespace Syriable\Translations\Glossary;

use Syriable\Translations\Models\GlossaryTerm;

/**
 * Provides glossary terms and their per-locale translations to consumers
 * (validation, AI translation). Results are cached per locale for the lifetime
 * of the instance to avoid repeated lookups during a run.
 */
final class GlossaryService
{
    /**
     * @var array<string, list<GlossaryEntry>>
     */
    private array $cache = [];

    public function enabled(): bool
    {
        return config('translations.metadata.enabled', true) === true;
    }

    /**
     * Glossary entries that have a translation for the given locale.
     *
     * @return list<GlossaryEntry>
     */
    public function forLocale(string $locale): array
    {
        if (! $this->enabled()) {
            return [];
        }

        return $this->cache[$locale] ??= $this->load($locale);
    }

    public function forget(?string $locale = null): void
    {
        if ($locale === null) {
            $this->cache = [];

            return;
        }

        unset($this->cache[$locale]);
    }

    /**
     * @return list<GlossaryEntry>
     */
    private function load(string $locale): array
    {
        $terms = GlossaryTerm::query()
            ->with(['translations' => fn ($query) => $query->where('locale', $locale)])
            ->get();

        $entries = [];

        foreach ($terms as $term) {
            $translation = $term->translations->first();

            if ($translation === null) {
                continue;
            }

            $entries[] = new GlossaryEntry(
                $term->source_term,
                $translation->translation,
                $term->case_sensitive,
                $term->exact_match,
            );
        }

        return $entries;
    }
}
