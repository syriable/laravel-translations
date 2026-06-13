<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Syriable\Translations\Enums\RevisionReason;

class Revision extends TranslationModel
{
    protected string $table_ = 'revisions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'reason' => RevisionReason::class,
            'meta' => 'array',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function scopeForLocale(Builder $query, int $localeId): Builder
    {
        return $query->whereHas('message', fn (Builder $sub) => $sub->where('locale_id', $localeId));
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $sub) => $sub->where('created_at', '>=', $from))
            ->when($to, fn (Builder $sub) => $sub->where('created_at', '<=', $to));
    }
}
