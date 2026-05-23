<?php

declare(strict_types=1);

namespace Syriable\Translations\Glossary;

/**
 * A resolved glossary term and its expected translation for a single locale.
 */
final readonly class GlossaryEntry
{
    public function __construct(
        public string $sourceTerm,
        public string $translation,
        public bool $caseSensitive,
        public bool $exactMatch,
    ) {}
}
