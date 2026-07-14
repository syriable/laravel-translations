<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Syriable\Translations\Analytics\BundleCoverage;

/**
 * @property int $id
 * @property string $name
 * @property string|null $namespace
 * @property string|null $file_path
 * @property string $format
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $phrases_count
 * @property-read int|null $translated_phrases_count
 */
class Bundle extends TranslationModel
{
    protected string $table_ = 'bundles';

    protected $guarded = [];

    public function phrases(): HasMany
    {
        return $this->hasMany(Phrase::class);
    }

    public function isJson(): bool
    {
        return $this->format === 'json';
    }

    public function label(): string
    {
        return $this->namespace ? "{$this->namespace}::{$this->name}" : $this->name;
    }

    public function scopeWithTranslationProgress(Builder $query): Builder
    {
        return app(BundleCoverage::class)->applyProgressCounts($query);
    }

    public function translationProgressPercent(): float
    {
        return app(BundleCoverage::class)->percent($this);
    }
}
