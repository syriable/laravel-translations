<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Syriable\Translations\Enums\Direction;
use Syriable\Translations\Enums\Tone;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $native_name
 * @property Direction $direction
 * @property bool $is_source
 * @property Tone $tone
 * @property bool $enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $messages_count
 * @property-read int|null $translated_messages_count
 * @property-read int|null $approved_messages_count
 * @property-read int|null $pending_review_messages_count
 * @property-read int|null $missing_messages_count
 * @property-read string $flag
 * @property-read int $translation_progress
 *
 * @method static \Illuminate\Database\Eloquent\Builder<Locale> enabled()
 * @method static \Illuminate\Database\Eloquent\Builder<Locale> targets()
 * @method static \Illuminate\Database\Eloquent\Builder<Locale> withTranslationProgressCounts()
 * @method \Illuminate\Database\Eloquent\Builder<Locale> withTranslationProgressCounts()
 */
class Locale extends TranslationModel
{
    protected string $table_ = 'locales';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'direction' => Direction::class,
            'tone' => Tone::class,
            'is_source' => 'boolean',
            'enabled' => 'boolean',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function translatedMessages(): HasMany
    {
        return $this->hasMany(Message::class)->translated();
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            config('translations.member_model'),
            config('translations.database.prefix', 'tx_').'member_locale',
            'locale_id',
            'member_id',
        );
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function scopeTargets(Builder $query): Builder
    {
        return $query->where('is_source', false);
    }

    public static function source(): ?self
    {
        return Cache::driver('array')->rememberForever('translations.source-locale', fn () => static::query()->where('is_source', true)->first());
    }

    public static function flushSourceCache(): void
    {
        Cache::driver('array')->forget('translations.source-locale');
    }

    public function scopeWithTranslationProgressCounts(Builder $query): Builder
    {
        return $query->withCount([
            'messages',
            'messages as translated_messages_count' => fn (Builder $query) => $query->translated(),
        ]);
    }

    public function flag(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'data:image/svg+xml;base64,'.base64_encode(svg('flag-language-'.$this->code)->toHtml()),
        );
    }

    public function translationProgress(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                $total = (int) ($this->messages_count ?? $this->messages()->count());

                if ($total === 0) {
                    return 0;
                }

                $translated = (int) ($this->translated_messages_count ?? $this->messages()->translated()->count());

                return (int) round($translated / $total * 100);
            },
        );
    }
}
