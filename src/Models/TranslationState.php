<?php

declare(strict_types=1);

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Builder;
use Syriable\Translations\Domain\Enums\ReviewStatus;

/**
 * The review state of a single translation (status, reviewer, feedback,
 * AI provenance). Keyed by locale + key; values themselves stay in lang files.
 *
 * @property string $locale
 * @property string $translation_key
 * @property string $key_hash
 * @property ReviewStatus $status
 * @property bool $ai_generated
 * @property string|null $reviewed_by
 * @property string|null $reviewer_feedback
 */
final class TranslationState extends TranslationMetadata
{
    protected $table = 'translation_states';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ReviewStatus::class,
        'ai_generated' => 'boolean',
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
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::NeedsReview);
    }
}
