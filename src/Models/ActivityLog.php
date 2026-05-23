<?php

declare(strict_types=1);

namespace Syriable\Translations\Models;

/**
 * An audit-trail entry recording a translation change (who did what, to which
 * locale/key, and when).
 *
 * @property string|null $user_id
 * @property string $action
 * @property string|null $locale
 * @property string|null $translation_key
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 */
final class ActivityLog extends TranslationMetadata
{
    public const UPDATED_AT = null;

    protected $table = 'translation_activity_logs';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];
}
