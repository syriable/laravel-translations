<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $term_id
 * @property int $locale_id
 * @property string $value
 * @property string|null $approved_by
 */
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
