<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $source
 * @property string|null $note
 * @property bool $case_sensitive
 * @property bool $whole_word
 * @property string|null $created_by
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TermDefinition> $definitions
 */
class Term extends TranslationModel
{
    protected string $table_ = 'terms';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'case_sensitive' => 'boolean',
            'whole_word' => 'boolean',
        ];
    }

    public function definitions(): HasMany
    {
        return $this->hasMany(TermDefinition::class);
    }

    public function definitionFor(int $localeId): ?TermDefinition
    {
        return TermDefinition::query()
            ->where('term_id', $this->id)
            ->where('locale_id', $localeId)
            ->first();
    }
}
