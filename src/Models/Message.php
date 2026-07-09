<?php

namespace Syriable\Translations\Models;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Syriable\Translations\Contracts\ResolvesActor;
use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Events\CommentPosted;
use Syriable\Translations\Events\MessageSaved;
use Syriable\Translations\Events\MessageStatusChanged;

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

    protected MessageStatus|string|null $originalStatusBeforeSave = null;

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

    public function translator(): BelongsTo
    {
        return $this->belongsTo(config('translations.member_model'), 'translated_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(config('translations.member_model'), 'reviewed_by');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(Revision::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(QualityIssue::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
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
        static::$stampChangedBy = $changedBy ?? static::resolveActor();
        static::$stampMeta = $meta;
    }

    public static function clearStamp(): void
    {
        static::$stampReason = null;
        static::$stampChangedBy = null;
        static::$stampMeta = [];
    }

    /**
     * Identify whoever is behind the current action when no explicit actor
     * was passed in - e.g. the authenticated user who clicked "translate
     * with AI", not the AI itself. See Contracts\ResolvesActor.
     */
    public static function resolveActor(): ?string
    {
        return app(ResolvesActor::class)->resolve();
    }

    /**
     * @param  Closure(?string $resolvedBy): mixed  $callback
     */
    public static function withStamp(?string $reason, ?string $changedBy, array $meta, Closure $callback): mixed
    {
        static::stamp($reason, $changedBy, $meta);

        try {
            return $callback(static::$stampChangedBy);
        } finally {
            static::clearStamp();
        }
    }

    public function comment(string $body, ?string $memberId = null, array $meta = []): Comment
    {
        $comment = $this->comments()->create([
            'member_id' => $memberId,
            'body' => $body,
            'meta' => $meta,
        ]);

        event(new CommentPosted($comment));

        return $comment;
    }

    protected static function booted(): void
    {
        static::saving(function (Message $message): void {
            $message->originalValueBeforeSave = $message->getOriginal('value');
            $message->originalStatusBeforeSave = $message->getOriginal('status');
        });

        static::saved(function (Message $message): void {
            if ($message->wasChanged('status') && $message->wasRecentlyCreated === false) {
                $oldStatus = $message->originalStatusBeforeSave instanceof MessageStatus
                    ? $message->originalStatusBeforeSave
                    : MessageStatus::tryFrom((string) $message->originalStatusBeforeSave);

                event(new MessageStatusChanged(
                    $message,
                    $oldStatus,
                    $message->status,
                    static::$stampReason,
                    static::$stampChangedBy,
                    static::$stampMeta,
                ));
            }

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
