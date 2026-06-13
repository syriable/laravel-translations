<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermDefinition extends TranslationModel
{
    protected string $table_ = 'term_definitions';

    protected $guarded = [];

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function locale(): BelongsTo
    {
        return $this->belongsTo(Locale::class);
    }
}
