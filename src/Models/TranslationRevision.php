<?php

declare(strict_types=1);

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Builder;
use Syriable\Translations\Domain\Enums\RevisionType;

/**
 * A point-in-time record of a change to a single translation value, kept for
 * history and attribution. Keyed by locale + key (a hash of the key is stored
 * so arbitrarily long keys remain indexable).
 *
 * @property string $locale
 * @property string $translation_key
 * @property string $key_hash
 * @property string|null $old_value
 * @property string|null $new_value
 * @property RevisionType $change_type
 * @property string|null $changed_by
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 */
final class TranslationRevision extends TranslationMetadata
{
    protected $table = 'translation_revisions';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'change_type' => RevisionType::class,
        'metadata' => 'array',
    ];

    public static function hashKey(string $key): string
    {
        return sha1($key);
    }

    /**
     * Limit the query to the revision history of a single translation.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForKey(Builder $query, string $locale, string $key): Builder
    {
        return $query->where('locale', $locale)->where('key_hash', self::hashKey($key));
    }
}
