<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string|null $member_id
 * @property string $action
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property array|null $meta
 * @property-read Model|null $member
 * @property-read Model|null $subject
 */
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

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(config('translations.member_model'), 'member_id');
    }
}
