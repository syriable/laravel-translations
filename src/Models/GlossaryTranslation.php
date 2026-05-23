<?php

declare(strict_types=1);

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The agreed translation of a glossary term for a single locale.
 *
 * @property int $glossary_term_id
 * @property string $locale
 * @property string $translation
 * @property string|null $approved_by
 */
final class GlossaryTranslation extends TranslationMetadata
{
    protected $table = 'translation_glossary_translations';

    /**
     * @return BelongsTo<GlossaryTerm, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(GlossaryTerm::class, 'glossary_term_id');
    }
}
