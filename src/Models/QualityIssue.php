<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Syriable\Translations\Enums\Severity;

/**
 * @property int $id
 * @property int $message_id
 * @property int $locale_id
 * @property string $check
 * @property Severity $severity
 * @property string $detail
 * @property string|null $suggestion
 * @property bool $fixable
 * @property array<string, mixed>|null $meta
 * @property-read Message $message
 * @property-read Locale $locale
 */
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
