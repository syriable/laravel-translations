<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Builder;

class IgnoredString extends TranslationModel
{
    protected string $table_ = 'ignored_strings';

    protected $guarded = [];

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('scope', 'global');
    }

    public function scopeForFile(Builder $query, string $path): Builder
    {
        return $query->where('file_path', $path);
    }
}
