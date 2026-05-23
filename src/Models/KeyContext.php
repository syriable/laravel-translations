<?php

declare(strict_types=1);

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * A place in the source code where a translation key is used (file + line +
 * helper). Contexts are per-key (language-agnostic) and produced by scanning
 * source with the AST extractor.
 *
 * @property string $translation_key
 * @property string $key_hash
 * @property string $file_path
 * @property int $line_number
 * @property string|null $helper
 * @property string|null $file_type
 */
final class KeyContext extends TranslationMetadata
{
    protected $table = 'translation_key_contexts';

    public static function hashKey(string $key): string
    {
        return sha1($key);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForKey(Builder $query, string $key): Builder
    {
        return $query->where('key_hash', self::hashKey($key));
    }
}
