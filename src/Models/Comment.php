<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $message_id
 * @property string|null $member_id
 * @property string $body
 * @property array|null $meta
 * @property-read Message $message
 * @property-read Model|null $member
 */
class Comment extends TranslationModel
{
    protected string $table_ = 'comments';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(config('translations.member_model'), 'member_id');
    }
}
