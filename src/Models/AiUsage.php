<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsage extends TranslationModel
{
    protected string $table_ = 'ai_usages';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'input_chars' => 'integer',
            'output_chars' => 'integer',
            'cost' => 'decimal:6',
            'success' => 'boolean',
        ];
    }

    public function phrase(): BelongsTo
    {
        return $this->belongsTo(Phrase::class);
    }
}
