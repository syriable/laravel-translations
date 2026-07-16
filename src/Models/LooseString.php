<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Syriable\Translations\Enums\LooseStringStatus;

/**
 * @property int $id
 * @property string $file_path
 * @property int|null $line
 * @property string $text
 * @property string $text_hash
 * @property string|null $element_type
 * @property string|null $scanner
 * @property LooseStringStatus $status
 * @property int|null $phrase_id
 * @property array<array-key, mixed>|null $placeholders
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Phrase|null $phrase
 */
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
