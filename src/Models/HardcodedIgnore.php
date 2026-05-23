<?php

declare(strict_types=1);

namespace Syriable\Translations\Models;

/**
 * A rule that suppresses a hardcoded string from detection results (matched by
 * its text hash), used to dismiss intentional literals.
 *
 * @property string|null $text_hash
 * @property string|null $file_path
 * @property string|null $note
 * @property string|null $created_by
 */
final class HardcodedIgnore extends TranslationMetadata
{
    protected $table = 'translation_hardcoded_ignores';
}
