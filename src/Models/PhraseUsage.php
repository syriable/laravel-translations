<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhraseUsage extends TranslationModel
{
    protected string $table_ = 'phrase_usages';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'line' => 'integer',
        ];
    }

    public function phrase(): BelongsTo
    {
        return $this->belongsTo(Phrase::class);
    }
}
