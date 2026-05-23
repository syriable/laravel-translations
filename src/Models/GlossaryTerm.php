<?php

declare(strict_types=1);

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A source-language term whose translation should stay consistent across the
 * catalog (e.g. a product or brand name with an agreed wording per locale).
 *
 * @property string $source_term
 * @property string|null $context
 * @property bool $case_sensitive
 * @property bool $exact_match
 * @property string|null $created_by
 */
final class GlossaryTerm extends TranslationMetadata
{
    protected $table = 'translation_glossary_terms';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'case_sensitive' => 'boolean',
        'exact_match' => 'boolean',
    ];

    /**
     * @return HasMany<GlossaryTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(GlossaryTranslation::class, 'glossary_term_id');
    }
}
