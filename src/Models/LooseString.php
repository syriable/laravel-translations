<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Syriable\Translations\Enums\LooseStringStatus;

class LooseString extends TranslationModel
{
    protected string $table_ = 'loose_strings';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'line' => 'integer',
            'status' => LooseStringStatus::class,
            'placeholders' => 'array',
        ];
    }

    public function phrase(): BelongsTo
    {
        return $this->belongsTo(Phrase::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', LooseStringStatus::Pending->value);
    }

    public function scopeInFile(Builder $query, string $path): Builder
    {
        return $query->where('file_path', $path);
    }
}
