<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Phrase extends TranslationModel
{
    protected string $table_ = 'phrases';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'placeholders' => 'array',
            'is_html' => 'boolean',
            'is_plural' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PhraseUsage::class);
    }

    public function dottedKey(): string
    {
        return "{$this->bundle->name}.{$this->key}";
    }

    public function scopeMissingIn(Builder $query, int $localeId): Builder
    {
        return $query->whereDoesntHave('messages', function (Builder $sub) use ($localeId): void {
            $sub->where('locale_id', $localeId)->where('status', '!=', 'open');
        });
    }
}
