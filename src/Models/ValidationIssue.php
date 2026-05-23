<?php

declare(strict_types=1);

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Builder;
use Syriable\Translations\Domain\Enums\IssueSeverity;

/**
 * A persisted validation finding for a single translation, surfaced in the
 * management UI. Keyed by locale + key (with a key hash for indexing).
 *
 * @property string $locale
 * @property string $translation_key
 * @property string $key_hash
 * @property string $check
 * @property IssueSeverity $severity
 * @property string $message
 * @property string|null $suggestion
 * @property bool $auto_fixable
 * @property array<string, mixed>|null $metadata
 */
final class ValidationIssue extends TranslationMetadata
{
    protected $table = 'translation_validation_issues';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'severity' => IssueSeverity::class,
        'auto_fixable' => 'boolean',
        'metadata' => 'array',
    ];

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

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }
}
