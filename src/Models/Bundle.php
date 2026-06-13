<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
