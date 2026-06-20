<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Syriable\Translations\Enums\Priority;

/**
 * @property int $id
 * @property int $bundle_id
 * @property string $key
 * @property string|null $note
 * @property array<array-key, mixed>|null $placeholders
 * @property bool $is_html
 * @property bool $is_plural
 * @property Priority $priority
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Bundle $bundle
 */
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
            'priority' => Priority::class,
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

    public function sourceMessage(): HasOne
    {
        return $this->hasOne(Message::class)->whereRelation('locale', 'is_source', true);
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
