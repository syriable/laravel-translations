<?php

namespace Syriable\Translations\Models;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Events\MessageSaved;

class Message extends TranslationModel
{
    protected string $table_ = 'messages';

    protected $guarded = [];

    protected ?string $originalValueBeforeSave = null;

    protected static ?string $stampReason = null;

    protected static ?string $stampChangedBy = null;

    protected static array $stampMeta = [];

    protected function casts(): array
    {
        return [
            'status' => MessageStatus::class,
            'ai_generated' => 'boolean',
        ];
    }

    public function phrase(): BelongsTo
    {
        return $this->belongsTo(Phrase::class);
    }

    public function sourceMessage(): HasOne
    {
        return $this->hasOne(self::class, 'phrase_id', 'phrase_id')
            ->whereRelation('locale', 'is_source', true);
    }

    public function locale(): BelongsTo
    {
        return $this->belongsTo(Locale::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(Revision::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(QualityIssue::class);
    }

    public function scopeTranslated(Builder $query): Builder
    {
        return $query->where('status', '!=', MessageStatus::Open->value);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', MessageStatus::Open->value);
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('status', MessageStatus::PendingReview->value);
    }

    public static function stamp(?string $reason, ?string $changedBy = null, array $meta = []): void
    {
        static::$stampReason = $reason;
        static::$stampChangedBy = $changedBy;
        static::$stampMeta = $meta;
    }

    public static function clearStamp(): void
    {
        static::$stampReason = null;
        static::$stampChangedBy = null;
        static::$stampMeta = [];
    }

    public static function withStamp(?string $reason, ?string $changedBy, array $meta, Closure $callback): mixed
    {
        static::stamp($reason, $changedBy, $meta);

        try {
            return $callback();
        } finally {
            static::clearStamp();
        }
    }

    protected static function booted(): void
    {
        static::saving(function (Message $message): void {
            $message->originalValueBeforeSave = $message->getOriginal('value');
        });

        static::saved(function (Message $message): void {
            if (! $message->wasChanged('value') && $message->wasRecentlyCreated === false) {
                return;
            }

            event(new MessageSaved(
                $message,
                $message->originalValueBeforeSave,
                static::$stampReason,
                static::$stampChangedBy,
                static::$stampMeta,
            ));
        });
    }
}
