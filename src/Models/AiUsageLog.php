<?php

declare(strict_types=1);

namespace Syriable\Translations\Models;

/**
 * A record of one AI translation run, for cost/usage analytics.
 *
 * @property string $provider
 * @property string|null $model
 * @property string $source_locale
 * @property string $target_locale
 * @property int $keys
 * @property int $input_characters
 * @property int $output_characters
 * @property string|null $estimated_cost
 * @property bool $success
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 */
final class AiUsageLog extends TranslationMetadata
{
    public const UPDATED_AT = null;

    protected $table = 'translation_ai_usage_logs';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'success' => 'boolean',
    ];
}
