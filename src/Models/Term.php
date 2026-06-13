<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->definitions->firstWhere('locale_id', $localeId);
    }
}
