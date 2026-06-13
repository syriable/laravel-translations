<?php

namespace Syriable\Translations\Support;

class TranslationRequest
{
    public function __construct(
        public readonly string $text,
        public readonly string $sourceLocale,
        public readonly string $targetLocale,
        public readonly ?string $tone = null,
        public readonly ?string $note = null,
        public readonly array $usages = [],
        public readonly array $siblings = [],
        public readonly array $glossary = [],
        public readonly int $variants = 1,
        public readonly ?string $provider = null,
        public readonly ?string $model = null,
    ) {}
}
