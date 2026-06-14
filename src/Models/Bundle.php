<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Syriable\Translations\Analytics\BundleCoverage;

class Bundle extends TranslationModel
{
    protected string $table_ = 'bundles';

    protected $guarded = [];

    public function phrases(): HasMany
    {
        return $this->hasMany(Phrase::class);
    }

    /**
     * @param  Builder<Bundle>  $query
     * @return Builder<Bundle>
     */
    public function scopeWithTranslationProgress(Builder $query): Builder
    {
        return app(BundleCoverage::class)->applyProgressCounts($query);
    }

    public function translationProgressPercent(): float
    {
        return app(BundleCoverage::class)->percent($this);
    }

    public function isJson(): bool
    {
        return $this->format === 'json';
    }

    public function label(): string
    {
        return $this->namespace ? "{$this->namespace}::{$this->name}" : $this->name;
    }
}
