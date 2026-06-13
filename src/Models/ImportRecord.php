<?php

namespace Syriable\Translations\Models;

class ImportRecord extends TranslationModel
{
    protected string $table_ = 'import_records';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fresh' => 'boolean',
        ];
    }
}
