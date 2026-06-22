<?php

namespace Syriable\Translations\Models;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Events\MessageSaved;

/**
 * @property int $id
 * @property int $phrase_id
 * @property int $locale_id
 * @property string|null $value
 * @property MessageStatus $status
 * @property string|null $translated_by
 * @property string|null $reviewed_by
 * @property string|null $review_note
 * @property bool $ai_generated
 * @property string|null $ai_provider
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $source
 * @property-read Locale $locale
 * @property-read Phrase $phrase
 * @property-read Message|null $sourceMessage
 */
class Message extends TranslationModel
{
    protected string $table_ = 'messages';

    protected $guarded = [];

    protected $appends = [
        'source',
    ];

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

    public function targetMessages(): HasMany
    {
        return $this->hasMany(self::class, 'phrase_id', 'phrase_id')
            ->whereRelation('locale', 'is_source', false);
    }

    public function source(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->locale->is_source) {
                true => $this->phrase->key,
                false => $this->sourceMessage?->value,
            },
        );
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
