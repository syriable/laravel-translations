<?php

namespace Syriable\Translations\Models;

class Activity extends TranslationModel
{
    public const UPDATED_AT = null;

    protected string $table_ = 'activities';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }
}
