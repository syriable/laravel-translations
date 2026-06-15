<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Syriable\Translations\Enums\Direction;

class Locale extends TranslationModel
{
    protected string $table_ = 'locales';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'direction' => Direction::class,
            'is_source' => 'boolean',
            'enabled' => 'boolean',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, config('translations.database.prefix', 'tx_').'member_locale');
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

    public function flag(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'data:image/svg+xml;base64,'.base64_encode(svg('flag-language-'.$this->code)->toHtml()),
        );
    }
}
