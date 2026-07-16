<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Bundle $bundle
 * @property-read Message|null $sourceMessage
 * @property-read Collection<int, Message> $messages
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

    /**
     * Whether the phrase defines any placeholder parameters that its
     * translations are required to preserve.
     */
    public function hasPlaceholders(): bool
    {
        return $this->placeholderNames() !== [];
    }

    /**
     * The placeholder parameters defined by the phrase, e.g.
     * `[':name', '{count}']`.
     *
     * @return list<string>
     */
    public function placeholderNames(): array
    {
        return array_values($this->placeholders ?? []);
    }

    /**
     * Split a key into its prefix segments, treating `.`, `_` and `-` as
     * boundaries. `accepted_if` → `['accepted', 'if']`.
     *
     * @return list<string>
     */
    public static function segments(string $key): array
    {
        $parts = preg_split('/[._-]+/', $key, flags: PREG_SPLIT_NO_EMPTY);

        return $parts === false || $parts === [] ? [$key] : $parts;
    }

    public function scopeMissingIn(Builder $query, int $localeId): Builder
    {
        return $query->whereDoesntHave('messages', function (Builder $sub) use ($localeId): void {
            $sub->where('locale_id', $localeId)->where('status', '!=', 'open');
        });
    }
}
