<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Syriable\Translations\Enums\Severity;

class QualityIssue extends TranslationModel
{
    protected string $table_ = 'quality_issues';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'severity' => Severity::class,
            'fixable' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function locale(): BelongsTo
    {
        return $this->belongsTo(Locale::class);
    }
}
