<?php

namespace Syriable\Translations\Glossary;

use Illuminate\Support\Collection;
use Syriable\Translations\Models\Term;
use Syriable\Translations\Models\TermDefinition;

class Glossary
{
    public function define(string $source, ?string $note = null, bool $caseSensitive = false, bool $wholeWord = false, ?string $createdBy = null): Term
    {
        return Term::query()->updateOrCreate(
            ['source' => $source],
            [
                'note' => $note,
                'case_sensitive' => $caseSensitive,
                'whole_word' => $wholeWord,
                'created_by' => $createdBy,
            ],
        );
    }

    public function translate(Term $term, int $localeId, string $value, ?string $approvedBy = null): TermDefinition
    {
        return $term->definitions()->updateOrCreate(
            ['locale_id' => $localeId],
            ['value' => $value, 'approved_by' => $approvedBy],
        );
    }

    public function forget(Term $term): void
    {
        $term->delete();
    }

    /** @return Collection<int, Term> */
    public function matching(string $text, int $localeId): Collection
    {
        return Term::query()
            ->with(['definitions' => fn ($query) => $query->where('locale_id', $localeId)])
            ->get()
            ->filter(fn (Term $term) => $this->mentions($text, $term) && $term->definitions->isNotEmpty())
            ->values();
    }

    /** @return array<string, string> */
    public function pairsFor(string $text, int $localeId): array
    {
        $pairs = [];

        foreach ($this->matching($text, $localeId) as $term) {
            $pairs[$term->source] = $term->definitions->first()->value;
        }

        return $pairs;
    }

    private function mentions(string $text, Term $term): bool
    {
        $haystack = $term->case_sensitive ? $text : mb_strtolower($text);
        $needle = $term->case_sensitive ? $term->source : mb_strtolower($term->source);

        if ($term->whole_word) {
            return (bool) preg_match('/\b'.preg_quote($needle, '/').'\b/u', $haystack);
        }

        return str_contains($haystack, $needle);
    }
}
