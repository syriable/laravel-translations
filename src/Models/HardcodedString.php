<?php

declare(strict_types=1);

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Builder;
use Syriable\Translations\Domain\Enums\HardcodedStatus;

/**
 * A hardcoded user-facing string discovered in source code, tracked through its
 * lifecycle (pending → ignored/converted) for the management UI.
 *
 * @property string $file_path
 * @property int $line_number
 * @property string $text
 * @property string $text_hash
 * @property string|null $element_type
 * @property string $scanner_type
 * @property HardcodedStatus $status
 * @property string|null $converted_key
 */
final class HardcodedString extends TranslationMetadata
{
    protected $table = 'translation_hardcoded_strings';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => HardcodedStatus::class,
    ];

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', HardcodedStatus::Pending);
    }
}
