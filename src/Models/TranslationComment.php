<?php

declare(strict_types=1);

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * A discussion note attached to a single translation, for collaboration between
 * translators and reviewers. Keyed by locale + key.
 *
 * @property string $locale
 * @property string $translation_key
 * @property string $key_hash
 * @property string|null $user_id
 * @property string $body
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 */
final class TranslationComment extends TranslationMetadata
{
    protected $table = 'translation_comments';

    public static function hashKey(string $key): string
    {
        return sha1($key);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForKey(Builder $query, string $locale, string $key): Builder
    {
        return $query->where('locale', $locale)->where('key_hash', self::hashKey($key));
    }
}
